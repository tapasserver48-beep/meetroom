<?php

namespace App\Providers;

use App\Models\Meeting;
use App\Policies\MeetingPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The mapping of policy classes to model classes.
     */
    protected $policies = [
        Meeting::class => MeetingPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Register policies defined in $policies property
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }
}