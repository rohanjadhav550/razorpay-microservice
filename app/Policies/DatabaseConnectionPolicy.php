<?php

namespace App\Policies;

use App\Models\DatabaseConnection;
use App\Models\User;

class DatabaseConnectionPolicy
{
    public function view(User $user, DatabaseConnection $connection): bool
    {
        return $user->id === $connection->user_id;
    }

    public function update(User $user, DatabaseConnection $connection): bool
    {
        return $user->id === $connection->user_id;
    }

    public function delete(User $user, DatabaseConnection $connection): bool
    {
        return $user->id === $connection->user_id;
    }
}
