<?php

namespace App\Policies;

use App\Models\SchemaProposal;
use App\Models\User;

class SchemaProposalPolicy
{
    public function view(User $user, SchemaProposal $proposal): bool
    {
        return $user->id === $proposal->user_id;
    }

    public function update(User $user, SchemaProposal $proposal): bool
    {
        return $user->id === $proposal->user_id;
    }

    public function delete(User $user, SchemaProposal $proposal): bool
    {
        return $user->id === $proposal->user_id;
    }

    public function approve(User $user, SchemaProposal $proposal): bool
    {
        return $user->id === $proposal->user_id;
    }

    public function apply(User $user, SchemaProposal $proposal): bool
    {
        return $user->id === $proposal->user_id;
    }
}
