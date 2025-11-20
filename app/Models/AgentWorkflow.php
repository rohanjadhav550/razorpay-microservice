<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentWorkflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'database_connection_id',
        'name',
        'description',
        'service_type',
        'custom_logic',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'custom_logic' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function databaseConnection(): BelongsTo
    {
        return $this->belongsTo(DatabaseConnection::class);
    }

    public function schemaProposals(): HasMany
    {
        return $this->hasMany(SchemaProposal::class, 'workflow_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(AgentConversation::class, 'workflow_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
