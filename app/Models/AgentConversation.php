<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'workflow_id',
        'title',
        'messages',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'messages' => 'array',
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

    public function addMessage(string $role, string $content): void
    {
        $messages = $this->messages ?? [];
        $messages[] = [
            'role' => $role,
            'content' => $content,
            'timestamp' => now()->toISOString(),
        ];
        $this->messages = $messages;
        $this->save();
    }

    public function getLastMessage(): ?array
    {
        $messages = $this->messages ?? [];

        return end($messages) ?: null;
    }
}
