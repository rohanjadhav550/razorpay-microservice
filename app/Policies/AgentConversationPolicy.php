<?php

namespace App\Policies;

use App\Models\AgentConversation;
use App\Models\User;

class AgentConversationPolicy
{
    public function view(User $user, AgentConversation $conversation): bool
    {
        return $user->id === $conversation->user_id;
    }

    public function update(User $user, AgentConversation $conversation): bool
    {
        return $user->id === $conversation->user_id;
    }

    public function delete(User $user, AgentConversation $conversation): bool
    {
        return $user->id === $conversation->user_id;
    }
}
