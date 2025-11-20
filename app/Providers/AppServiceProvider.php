<?php

namespace App\Providers;

use App\Models\AgentConversation;
use App\Models\DatabaseConnection;
use App\Models\SchemaProposal;
use App\Policies\AgentConversationPolicy;
use App\Policies\DatabaseConnectionPolicy;
use App\Policies\SchemaProposalPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(DatabaseConnection::class, DatabaseConnectionPolicy::class);
        Gate::policy(AgentConversation::class, AgentConversationPolicy::class);
        Gate::policy(SchemaProposal::class, SchemaProposalPolicy::class);
    }
}
