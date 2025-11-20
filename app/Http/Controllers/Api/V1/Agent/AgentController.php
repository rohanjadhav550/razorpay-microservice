<?php

namespace App\Http\Controllers\Api\V1\Agent;

use App\Http\Controllers\Controller;
use App\Models\AgentConversation;
use App\Models\DatabaseConnection;
use App\Models\SchemaProposal;
use App\Services\AI\AIService;
use App\Services\Database\DatabaseAnalyzer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function conversations(Request $request): JsonResponse
    {
        $conversations = $request->user()
            ->conversations()
            ->with('workflow:id,name')
            ->select(['id', 'workflow_id', 'title', 'status', 'created_at', 'updated_at'])
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $conversations,
        ]);
    }

    public function startConversation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
        ]);

        $conversation = $request->user()->conversations()->create([
            'title' => $validated['title'] ?? 'New Conversation',
            'messages' => [],
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Conversation started.',
            'data' => $conversation,
        ], 201);
    }

    public function chat(Request $request, AgentConversation $conversation): JsonResponse
    {
        $this->authorize('update', $conversation);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:10000'],
            'database_connection_id' => ['sometimes', 'exists:database_connections,id'],
        ]);

        // Add user message
        $conversation->addMessage('user', $validated['message']);

        try {
            $aiService = new AIService($request->user());

            // Get existing schema if database connection provided
            $existingSchema = null;
            if (isset($validated['database_connection_id'])) {
                $dbConnection = DatabaseConnection::findOrFail($validated['database_connection_id']);
                $analyzer = new DatabaseAnalyzer($dbConnection);
                $existingSchema = $analyzer->getSchema();
            }

            // Build conversation history for AI
            $messages = array_map(function ($msg) {
                return [
                    'role' => $msg['role'],
                    'content' => $msg['content'],
                ];
            }, $conversation->messages);

            // Get AI response
            $response = $aiService->analyzeRequirements(
                $validated['message'],
                $existingSchema
            );

            // Add AI response
            $conversation->addMessage('assistant', $response);

            // Try to parse as JSON for structured response
            $parsedResponse = json_decode($response, true);

            return response()->json([
                'success' => true,
                'data' => [
                    'message' => $response,
                    'structured' => $parsedResponse,
                    'conversation_id' => $conversation->id,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI processing failed: '.$e->getMessage(),
            ], 500);
        }
    }

    public function getConversation(Request $request, AgentConversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        return response()->json([
            'success' => true,
            'data' => $conversation,
        ]);
    }

    public function generateProposal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'conversation_id' => ['required', 'exists:agent_conversations,id'],
            'database_connection_id' => ['required', 'exists:database_connections,id'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $conversation = AgentConversation::findOrFail($validated['conversation_id']);
        $this->authorize('view', $conversation);

        $dbConnection = DatabaseConnection::findOrFail($validated['database_connection_id']);

        try {
            $aiService = new AIService($request->user());
            $analyzer = new DatabaseAnalyzer($dbConnection);

            $existingSchema = $analyzer->getSchema();

            // Get the last assistant message which should contain the analysis
            $lastMessage = $conversation->getLastMessage();
            $analysis = json_decode($lastMessage['content'] ?? '{}', true);

            // Generate ER diagram
            $proposedTables = $analysis['proposed_tables'] ?? [];
            $erDiagram = $aiService->generateERDiagram($proposedTables);

            // Generate migrations
            $migrations = $aiService->generateMigrations(
                $analysis['proposed_tables'] ?? [],
                $dbConnection->driver
            );

            // Create schema proposal
            $proposal = SchemaProposal::create([
                'user_id' => $request->user()->id,
                'workflow_id' => $conversation->workflow_id,
                'database_connection_id' => $dbConnection->id,
                'description' => $validated['description'],
                'er_diagram' => $erDiagram,
                'proposed_changes' => $analysis,
                'migrations' => ['sql' => $migrations],
                'status' => 'pending',
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Schema proposal generated.',
                'data' => [
                    'proposal_id' => $proposal->id,
                    'er_diagram' => $erDiagram,
                    'proposed_changes' => $analysis,
                    'migrations' => $migrations,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate proposal: '.$e->getMessage(),
            ], 500);
        }
    }

    public function proposals(Request $request): JsonResponse
    {
        $proposals = $request->user()
            ->schemaProposals()
            ->with(['databaseConnection:id,name', 'workflow:id,name'])
            ->select(['id', 'workflow_id', 'database_connection_id', 'description', 'status', 'created_at', 'approved_at', 'applied_at'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $proposals,
        ]);
    }

    public function showProposal(Request $request, SchemaProposal $proposal): JsonResponse
    {
        $this->authorize('view', $proposal);

        return response()->json([
            'success' => true,
            'data' => $proposal,
        ]);
    }

    public function approveProposal(Request $request, SchemaProposal $proposal): JsonResponse
    {
        $this->authorize('update', $proposal);

        if (! $proposal->isPending()) {
            return response()->json([
                'success' => false,
                'message' => 'Proposal is not pending approval.',
            ], 400);
        }

        $proposal->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Proposal approved. You can now apply the migrations.',
            'data' => $proposal->only(['id', 'status', 'approved_at']),
        ]);
    }

    public function applyProposal(Request $request, SchemaProposal $proposal): JsonResponse
    {
        $this->authorize('update', $proposal);

        if (! $proposal->isApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'Proposal must be approved before applying.',
            ], 400);
        }

        try {
            $dbConnection = $proposal->databaseConnection;
            $analyzer = new DatabaseAnalyzer($dbConnection);

            $migrations = $proposal->migrations['sql'] ?? '';

            // Split migrations into individual statements and execute
            $statements = array_filter(
                array_map('trim', explode(';', $migrations))
            );

            foreach ($statements as $statement) {
                if (! empty($statement)) {
                    $analyzer->executeStatement($statement);
                }
            }

            $proposal->update([
                'status' => 'applied',
                'applied_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Migrations applied successfully.',
                'data' => $proposal->only(['id', 'status', 'applied_at']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to apply migrations: '.$e->getMessage(),
            ], 500);
        }
    }
}