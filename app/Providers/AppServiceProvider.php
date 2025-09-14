<?php
declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Modules\Post\Authorization\PostPolicy;
use App\Modules\Post\Models\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Post::class, PostPolicy::class);

        Model::shouldBeStrict();
        Relation::enforceMorphMap([
            'user' => User::class,
        ]);
    }
}
