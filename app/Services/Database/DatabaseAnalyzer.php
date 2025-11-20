<?php

namespace App\Services\Database;

use App\Models\DatabaseConnection;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

class DatabaseAnalyzer
{
    private Connection $connection;

    private string $driver;

    public function __construct(DatabaseConnection $databaseConnection)
    {
        $config = $databaseConnection->getConnectionConfig();
        $connectionName = 'external_'.$databaseConnection->id;

        config(["database.connections.{$connectionName}" => $config]);

        $this->connection = DB::connection($connectionName);
        $this->driver = $databaseConnection->driver;
    }

    public function testConnection(): bool
    {
        try {
            $this->connection->getPdo();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getSchema(): array
    {
        $tables = $this->getTables();
        $schema = [];

        foreach ($tables as $table) {
            $schema[$table] = [
                'columns' => $this->getColumns($table),
                'indexes' => $this->getIndexes($table),
                'foreign_keys' => $this->getForeignKeys($table),
            ];
        }

        return $schema;
    }

    public function getTables(): array
    {
        return match ($this->driver) {
            'mysql' => $this->getMySQLTables(),
            'pgsql' => $this->getPostgresTables(),
            'sqlite' => $this->getSQLiteTables(),
            'sqlsrv' => $this->getSQLServerTables(),
            default => [],
        };
    }

    public function getColumns(string $table): array
    {
        return match ($this->driver) {
            'mysql' => $this->getMySQLColumns($table),
            'pgsql' => $this->getPostgresColumns($table),
            'sqlite' => $this->getSQLiteColumns($table),
            'sqlsrv' => $this->getSQLServerColumns($table),
            default => [],
        };
    }

    public function getIndexes(string $table): array
    {
        return match ($this->driver) {
            'mysql' => $this->getMySQLIndexes($table),
            'pgsql' => $this->getPostgresIndexes($table),
            'sqlite' => $this->getSQLiteIndexes($table),
            'sqlsrv' => $this->getSQLServerIndexes($table),
            default => [],
        };
    }

    public function getForeignKeys(string $table): array
    {
        return match ($this->driver) {
            'mysql' => $this->getMySQLForeignKeys($table),
            'pgsql' => $this->getPostgresForeignKeys($table),
            'sqlite' => $this->getSQLiteForeignKeys($table),
            'sqlsrv' => $this->getSQLServerForeignKeys($table),
            default => [],
        };
    }

    public function executeQuery(string $query): array
    {
        return $this->connection->select($query);
    }

    public function executeStatement(string $statement): bool
    {
        return $this->connection->statement($statement);
    }

    // MySQL implementations
    private function getMySQLTables(): array
    {
        $results = $this->connection->select('SHOW TABLES');
        $key = 'Tables_in_'.$this->connection->getDatabaseName();

        return array_map(fn ($row) => $row->$key, $results);
    }

    private function getMySQLColumns(string $table): array
    {
        $results = $this->connection->select("DESCRIBE `{$table}`");

        return array_map(fn ($row) => [
            'name' => $row->Field,
            'type' => $row->Type,
            'nullable' => $row->Null === 'YES',
            'default' => $row->Default,
            'key' => $row->Key,
            'extra' => $row->Extra,
        ], $results);
    }

    private function getMySQLIndexes(string $table): array
    {
        $results = $this->connection->select("SHOW INDEX FROM `{$table}`");

        $indexes = [];
        foreach ($results as $row) {
            $name = $row->Key_name;
            if (! isset($indexes[$name])) {
                $indexes[$name] = [
                    'name' => $name,
                    'columns' => [],
                    'unique' => ! $row->Non_unique,
                ];
            }
            $indexes[$name]['columns'][] = $row->Column_name;
        }

        return array_values($indexes);
    }

    private function getMySQLForeignKeys(string $table): array
    {
        $results = $this->connection->select("
            SELECT
                CONSTRAINT_NAME, COLUMN_NAME,
                REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$this->connection->getDatabaseName(), $table]);

        return array_map(fn ($row) => [
            'name' => $row->CONSTRAINT_NAME,
            'column' => $row->COLUMN_NAME,
            'references_table' => $row->REFERENCED_TABLE_NAME,
            'references_column' => $row->REFERENCED_COLUMN_NAME,
        ], $results);
    }

    // PostgreSQL implementations
    private function getPostgresTables(): array
    {
        $results = $this->connection->select("
            SELECT tablename FROM pg_tables
            WHERE schemaname = 'public'
        ");

        return array_map(fn ($row) => $row->tablename, $results);
    }

    private function getPostgresColumns(string $table): array
    {
        $results = $this->connection->select("
            SELECT column_name, data_type, is_nullable, column_default
            FROM information_schema.columns
            WHERE table_name = ?
        ", [$table]);

        return array_map(fn ($row) => [
            'name' => $row->column_name,
            'type' => $row->data_type,
            'nullable' => $row->is_nullable === 'YES',
            'default' => $row->column_default,
        ], $results);
    }

    private function getPostgresIndexes(string $table): array
    {
        $results = $this->connection->select("
            SELECT indexname, indexdef
            FROM pg_indexes
            WHERE tablename = ?
        ", [$table]);

        return array_map(fn ($row) => [
            'name' => $row->indexname,
            'definition' => $row->indexdef,
        ], $results);
    }

    private function getPostgresForeignKeys(string $table): array
    {
        $results = $this->connection->select("
            SELECT
                tc.constraint_name,
                kcu.column_name,
                ccu.table_name AS foreign_table_name,
                ccu.column_name AS foreign_column_name
            FROM information_schema.table_constraints AS tc
            JOIN information_schema.key_column_usage AS kcu
                ON tc.constraint_name = kcu.constraint_name
            JOIN information_schema.constraint_column_usage AS ccu
                ON ccu.constraint_name = tc.constraint_name
            WHERE tc.constraint_type = 'FOREIGN KEY' AND tc.table_name = ?
        ", [$table]);

        return array_map(fn ($row) => [
            'name' => $row->constraint_name,
            'column' => $row->column_name,
            'references_table' => $row->foreign_table_name,
            'references_column' => $row->foreign_column_name,
        ], $results);
    }

    // SQLite implementations
    private function getSQLiteTables(): array
    {
        $results = $this->connection->select("
            SELECT name FROM sqlite_master
            WHERE type='table' AND name NOT LIKE 'sqlite_%'
        ");

        return array_map(fn ($row) => $row->name, $results);
    }

    private function getSQLiteColumns(string $table): array
    {
        $results = $this->connection->select("PRAGMA table_info(`{$table}`)");

        return array_map(fn ($row) => [
            'name' => $row->name,
            'type' => $row->type,
            'nullable' => ! $row->notnull,
            'default' => $row->dflt_value,
            'primary_key' => (bool) $row->pk,
        ], $results);
    }

    private function getSQLiteIndexes(string $table): array
    {
        $results = $this->connection->select("PRAGMA index_list(`{$table}`)");

        return array_map(fn ($row) => [
            'name' => $row->name,
            'unique' => (bool) $row->unique,
        ], $results);
    }

    private function getSQLiteForeignKeys(string $table): array
    {
        $results = $this->connection->select("PRAGMA foreign_key_list(`{$table}`)");

        return array_map(fn ($row) => [
            'column' => $row->from,
            'references_table' => $row->table,
            'references_column' => $row->to,
        ], $results);
    }

    // SQL Server implementations
    private function getSQLServerTables(): array
    {
        $results = $this->connection->select("
            SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_TYPE = 'BASE TABLE'
        ");

        return array_map(fn ($row) => $row->TABLE_NAME, $results);
    }

    private function getSQLServerColumns(string $table): array
    {
        $results = $this->connection->select("
            SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME = ?
        ", [$table]);

        return array_map(fn ($row) => [
            'name' => $row->COLUMN_NAME,
            'type' => $row->DATA_TYPE,
            'nullable' => $row->IS_NULLABLE === 'YES',
            'default' => $row->COLUMN_DEFAULT,
        ], $results);
    }

    private function getSQLServerIndexes(string $table): array
    {
        $results = $this->connection->select("
            SELECT i.name, i.is_unique
            FROM sys.indexes i
            JOIN sys.tables t ON i.object_id = t.object_id
            WHERE t.name = ? AND i.name IS NOT NULL
        ", [$table]);

        return array_map(fn ($row) => [
            'name' => $row->name,
            'unique' => (bool) $row->is_unique,
        ], $results);
    }

    private function getSQLServerForeignKeys(string $table): array
    {
        $results = $this->connection->select("
            SELECT
                fk.name AS constraint_name,
                COL_NAME(fkc.parent_object_id, fkc.parent_column_id) AS column_name,
                OBJECT_NAME(fk.referenced_object_id) AS referenced_table,
                COL_NAME(fkc.referenced_object_id, fkc.referenced_column_id) AS referenced_column
            FROM sys.foreign_keys fk
            JOIN sys.foreign_key_columns fkc ON fk.object_id = fkc.constraint_object_id
            WHERE OBJECT_NAME(fk.parent_object_id) = ?
        ", [$table]);

        return array_map(fn ($row) => [
            'name' => $row->constraint_name,
            'column' => $row->column_name,
            'references_table' => $row->referenced_table,
            'references_column' => $row->referenced_column,
        ], $results);
    }

    public function generateMermaidERDiagram(array $schema): string
    {
        $mermaid = "erDiagram\n";

        foreach ($schema as $tableName => $tableInfo) {
            $mermaid .= "    {$tableName} {\n";

            foreach ($tableInfo['columns'] as $column) {
                $type = strtoupper(preg_replace('/\(.*\)/', '', $column['type']));
                $pk = isset($column['primary_key']) && $column['primary_key'] ? 'PK' : '';
                $pk = $pk ?: (isset($column['key']) && $column['key'] === 'PRI' ? 'PK' : '');
                $mermaid .= "        {$type} {$column['name']} {$pk}\n";
            }

            $mermaid .= "    }\n";
        }

        // Add relationships
        foreach ($schema as $tableName => $tableInfo) {
            foreach ($tableInfo['foreign_keys'] as $fk) {
                $refTable = $fk['references_table'];
                $mermaid .= "    {$tableName} }|--|| {$refTable} : \"{$fk['column']}\"\n";
            }
        }

        return $mermaid;
    }
}
