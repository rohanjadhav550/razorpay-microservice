<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AgentConversation;
use App\Models\AgentWorkflow;
use App\Models\DatabaseConnection;
use App\Models\SchemaProposal;
use App\Services\AI\AIService;
use App\Services\Database\DatabaseAnalyzer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AgentController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $conversations = AgentConversation::where('user_id', $user->id)
            ->with(['workflow', 'databaseConnection'])
            ->latest()
            ->take(10)
            ->get();

        $workflows = AgentWorkflow::where('user_id', $user->id)
            ->with('schemaProposals')
            ->latest()
            ->take(10)
            ->get();

        $connections = DatabaseConnection::where('user_id', $user->id)->get();

        return Inertia::render('Agent/Index', [
            'conversations' => $conversations,
            'workflows' => $workflows,
            'connections' => $connections,
        ]);
    }

    public function createConversation(Request $request)
    {
        $validated = $request->validate([
            'database_connection_id' => ['nullable', 'exists:database_connections,id'],
        ]);

        $user = $request->user();

        if (! $user->hasAIConfigured()) {
            return back()->withErrors(['ai' => 'Please configure your AI provider settings first.']);
        }

        $conversation = AgentConversation::create([
            'user_id' => $user->id,
            'database_connection_id' => $validated['database_connection_id'] ?? null,
            'messages' => [],
        ]);

        return redirect()->route('agent.conversation', $conversation->id);
    }

    public function conversation(Request $request, AgentConversation $conversation): Response
    {
        $this->authorize('view', $conversation);

        $connections = DatabaseConnection::where('user_id', $request->user()->id)->get();

        return Inertia::render('Agent/Conversation', [
            'conversation' => $conversation->load(['workflow.schemaProposals', 'databaseConnection']),
            'connections' => $connections,
        ]);
    }

    public function chat(Request $request, AgentConversation $conversation)
    {
        $this->authorize('update', $conversation);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:10000'],
        ]);

        $user = $request->user();

        if (! $user->hasAIConfigured()) {
            return back()->withErrors(['ai' => 'Please configure your AI provider settings first.']);
        }

        // Add user message
        $messages = $conversation->messages ?? [];
        $messages[] = [
            'role' => 'user',
            'content' => $validated['message'],
            'timestamp' => now()->toISOString(),
        ];

        // Get AI response
        try {
            $aiService = new AIService($user);
            $systemPrompt = $this->buildSystemPrompt($conversation);

            $response = $aiService->chat($messages, $systemPrompt);

            $messages[] = [
                'role' => 'assistant',
                'content' => $response,
                'timestamp' => now()->toISOString(),
            ];

            $conversation->update(['messages' => $messages]);

            return back()->with('success', 'Message sent successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['ai' => 'Failed to get AI response: ' . $e->getMessage()]);
        }
    }

    public function generateProposal(Request $request, AgentConversation $conversation)
    {
        // Increase timeout for AI API calls (2 minutes)
        set_time_limit(300);
        \Log::info('Main');

        $this->authorize('update', $conversation);

        $user = $request->user();

        if (! $user->hasAIConfigured()) {
            return back()->withErrors(['ai' => 'Please configure your AI provider settings first.']);
        }

        if (! $conversation->database_connection_id) {
            return back()->withErrors(['database' => 'Please select a database connection first.']);
        }

        try {
            \Log::info('here 1');
            $connection = $conversation->databaseConnection;
            $analyzer = new DatabaseAnalyzer($connection);
            $currentSchema = $analyzer->getSchema();
            $erDiagram = $analyzer->generateMermaidERDiagram($currentSchema);

            $aiService = new AIService($user);

            // Analyze requirements from conversation
            $conversationText = collect($conversation->messages)
                ->map(fn($m) => "{$m['role']}: {$m['content']}")
                ->implode("\n");

            $requirements = $aiService->analyzeRequirements($conversationText);
            \Log::info('here 2');
            // Generate ER diagram for proposed changes
            $proposedDiagram = $aiService->generateERDiagram($requirements, $currentSchema);
            \Log::info('here 3');
            // Generate migrations
            $migrations = $aiService->generateMigrations($requirements, $currentSchema);
            \Log::info('here 4');
            // Create workflow if not exists
            $workflow = $conversation->workflow;
            if (! $workflow) {
                $workflow = AgentWorkflow::create([
                    'user_id' => $user->id,
                    'database_connection_id' => $connection->id,
                    'name' => 'Workflow from conversation #' . $conversation->id,
                    'description' => $requirements,
                    'service_type' => 'schema_design',
                    'status' => 'analyzing',
                ]);

                $conversation->update(['workflow_id' => $workflow->id]);
            } else {
                // Delete existing proposals for this workflow to reset
                $workflow->schemaProposals()->delete();
                $workflow->update([
                    'description' => $requirements,
                    'status' => 'analyzing',
                ]);
            }
            \Log::info('here 5');
            // Create schema proposal
            $proposal = SchemaProposal::create([
                'user_id' => $user->id,
                'workflow_id' => $workflow->id,
                'database_connection_id' => $connection->id,
                'description' => $requirements,
                'er_diagram' => $proposedDiagram,
                'proposed_changes' => json_encode($currentSchema),
                'migrations' => $migrations,
                'status' => 'pending',
            ]);
            \Log::info('Proposal------>', [$proposal]);
            $workflow->update(['status' => 'proposed']);

            return back()->with([
                'success' => 'Schema proposal generated successfully.',
                'proposal_id' => $proposal->id,
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['ai' => 'Failed to generate proposal: ' . $e->getMessage()]);
        }
    }

    public function approveProposal(Request $request, SchemaProposal $proposal)
    {
        $this->authorize('approve', $proposal);

        $proposal->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Proposal approved successfully.');
    }

    public function rejectProposal(Request $request, SchemaProposal $proposal)
    {
        $this->authorize('approve', $proposal);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $proposal->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['reason'] ?? null,
        ]);

        $proposal->workflow->update(['status' => 'rejected']);

        return back()->with('success', 'Proposal rejected.');
    }

    public function applyMigrations(Request $request, SchemaProposal $proposal)
    {
        $this->authorize('apply', $proposal);

        if ($proposal->status !== 'approved') {
            return back()->withErrors(['proposal' => 'Proposal must be approved before applying migrations.']);
        }

        try {
            $connection = $proposal->workflow->databaseConnection;
            $analyzer = new DatabaseAnalyzer($connection);

            \Log::info('Applying migrations', [
                'proposal_id' => $proposal->id,
                'connection_id' => $connection->id,
                'driver' => $connection->driver,
            ]);

            // Handle migrations as string (SQL) or array
            $migrations = $proposal->migrations;
            \Log::info('Migrations data', ['type' => gettype($migrations), 'content' => $migrations]);

            if (is_string($migrations)) {
                // Execute as raw SQL
                \Log::info('Executing migrations as string');
                $analyzer->executeMigration($migrations);
            } elseif (is_array($migrations)) {
                \Log::info('Executing migrations as array', ['count' => count($migrations)]);
                foreach ($migrations as $index => $migration) {
                    $sql = is_array($migration) ? ($migration['sql'] ?? '') : $migration;
                    if ($sql) {
                        \Log::info("Executing migration {$index}", ['sql' => substr($sql, 0, 200)]);
                        $analyzer->executeMigration($sql);
                    }
                }
            }

            $proposal->update([
                'status' => 'applied',
                'applied_at' => now(),
            ]);

            $proposal->workflow->update(['status' => 'completed']);

            \Log::info('Migrations applied successfully', ['proposal_id' => $proposal->id]);

            return back()->with('success', 'Migrations applied successfully.');
        } catch (\Exception $e) {
            \Log::error('Migration failed', [
                'proposal_id' => $proposal->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $proposal->update([
                'status' => 'failed',
            ]);

            return back()->withErrors(['migration' => 'Failed to apply migrations: ' . $e->getMessage()]);
        }
    }

    public function retryMigration(Request $request, SchemaProposal $proposal)
    {
        $this->authorize('apply', $proposal);

        if ($proposal->status !== 'failed') {
            return back()->withErrors(['proposal' => 'Only failed proposals can be retried.']);
        }

        // Reset status to approved so user can try again
        $proposal->update([
            'status' => 'approved',
        ]);

        $proposal->workflow->update(['status' => 'proposed']);

        return back()->with('success', 'Proposal status reset. You can now try applying migrations again.');
    }

    public function storeConnection(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'driver' => ['required', 'string', 'in:mysql,pgsql,sqlite,sqlsrv'],
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'database' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['user_id'] = $request->user()->id;

        $connection = DatabaseConnection::create($validated);

        return back()->with([
            'success' => 'Database connection created successfully.',
            'connection_id' => $connection->id,
        ]);
    }

    public function testConnection(Request $request, DatabaseConnection $connection)
    {
        $this->authorize('view', $connection);

        try {
            $analyzer = new DatabaseAnalyzer($connection);
            $analyzer->testConnection();

            return back()->with('success', 'Connection successful.');
        } catch (\Exception $e) {
            return back()->withErrors(['connection' => 'Connection failed: ' . $e->getMessage()]);
        }
    }

    protected function buildSystemPrompt(AgentConversation $conversation): string
    {
        $prompt = "You are an AI database architect assistant. Help users design and modify database schemas. ";
        $prompt .= "When the user describes their requirements, analyze them and suggest appropriate database changes. ";
        $prompt .= "Ask clarifying questions if needed. Be concise and technical.";

        if ($conversation->database_connection_id) {
            try {
                $analyzer = new DatabaseAnalyzer($conversation->databaseConnection);
                $schema = $analyzer->getSchema();
                $prompt .= "\n\nCurrent database schema:\n" . json_encode($schema, JSON_PRETTY_PRINT);
            } catch (\Exception $e) {
                $prompt .= "\n\nNote: Unable to fetch current schema: " . $e->getMessage();
            }
        }

        return $prompt;
    }
}