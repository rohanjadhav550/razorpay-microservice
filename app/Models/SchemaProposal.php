<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchemaProposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'workflow_id',
        'database_connection_id',
        'description',
        'er_diagram',
        'proposed_changes',
        'migrations',
        'status',
        'approved_at',
        'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'proposed_changes' => 'array',
            'migrations' => 'array',
            'approved_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(AgentWorkflow::class, 'workflow_id');
    }

    public function databaseConnection(): BelongsTo
    {
        return $this->belongsTo(DatabaseConnection::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isApplied(): bool
    {
        return $this->status === 'applied';
    }
}
