<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DatabaseConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'driver',
        'host',
        'port',
        'database',
        'username',
        'password',
        'is_active',
        'last_connected_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'host' => 'encrypted',
            'database' => 'encrypted',
            'username' => 'encrypted',
            'password' => 'encrypted',
            'is_active' => 'boolean',
            'last_connected_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workflows(): HasMany
    {
        return $this->hasMany(AgentWorkflow::class);
    }

    public function schemaProposals(): HasMany
    {
        return $this->hasMany(SchemaProposal::class);
    }

    public function getConnectionConfig(): array
    {
        $config = [
            'driver' => $this->driver,
            'database' => $this->database,
        ];

        if ($this->driver !== 'sqlite') {
            $config['host'] = $this->host;
            $config['port'] = $this->port ?? $this->getDefaultPort();
            $config['username'] = $this->username;
            $config['password'] = $this->password;
            $config['charset'] = $this->driver === 'mysql' ? 'utf8mb4' : 'utf8';
        }

        return $config;
    }

    private function getDefaultPort(): int
    {
        return match ($this->driver) {
            'mysql' => 3306,
            'pgsql' => 5432,
            'sqlsrv' => 1433,
            default => 3306,
        };
    }
}
