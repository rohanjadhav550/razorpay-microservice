<?php

namespace App\Http\Controllers\Api\V1\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Agent\StoreDatabaseConnectionRequest;
use App\Models\DatabaseConnection;
use App\Services\Database\DatabaseAnalyzer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DatabaseConnectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $connections = $request->user()
            ->databaseConnections()
            ->select(['id', 'name', 'driver', 'is_active', 'last_connected_at', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $connections,
        ]);
    }

    public function store(StoreDatabaseConnectionRequest $request): JsonResponse
    {
        $connection = $request->user()->databaseConnections()->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Database connection created successfully.',
            'data' => $connection->only(['id', 'name', 'driver', 'is_active', 'created_at']),
        ], 201);
    }

    public function show(Request $request, DatabaseConnection $connection): JsonResponse
    {
        $this->authorize('view', $connection);

        return response()->json([
            'success' => true,
            'data' => $connection->only(['id', 'name', 'driver', 'host', 'port', 'database', 'is_active', 'last_connected_at', 'created_at']),
        ]);
    }

    public function update(StoreDatabaseConnectionRequest $request, DatabaseConnection $connection): JsonResponse
    {
        $this->authorize('update', $connection);

        $connection->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Database connection updated successfully.',
            'data' => $connection->only(['id', 'name', 'driver', 'is_active', 'updated_at']),
        ]);
    }

    public function destroy(Request $request, DatabaseConnection $connection): JsonResponse
    {
        $this->authorize('delete', $connection);

        $connection->delete();

        return response()->json([
            'success' => true,
            'message' => 'Database connection deleted successfully.',
        ]);
    }

    public function test(Request $request, DatabaseConnection $connection): JsonResponse
    {
        $this->authorize('view', $connection);

        try {
            $analyzer = new DatabaseAnalyzer($connection);
            $connected = $analyzer->testConnection();

            if ($connected) {
                $connection->update(['last_connected_at' => now()]);

                return response()->json([
                    'success' => true,
                    'message' => 'Connection successful.',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Connection failed.',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection failed: '.$e->getMessage(),
            ], 400);
        }
    }

    public function schema(Request $request, DatabaseConnection $connection): JsonResponse
    {
        $this->authorize('view', $connection);

        try {
            $analyzer = new DatabaseAnalyzer($connection);
            $schema = $analyzer->getSchema();
            $erDiagram = $analyzer->generateMermaidERDiagram($schema);

            $connection->update(['last_connected_at' => now()]);

            return response()->json([
                'success' => true,
                'data' => [
                    'schema' => $schema,
                    'er_diagram' => $erDiagram,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch schema: '.$e->getMessage(),
            ], 400);
        }
    }
}
