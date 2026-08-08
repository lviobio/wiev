<?php
declare(strict_types=1);

namespace App\Modules\Post\Providers;

use App\Modules\Post\Models\Post;
use App\Modules\Post\Policies\PostPolicy;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PostModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Relation::morphMap([
            'post' => Post::class,
        ]);

        // Laravel's policy auto-discovery looks for App\Policies\PostPolicy; the module
        // keeps its policy next to the model instead, so the mapping is explicit.
        Gate::policy(Post::class, PostPolicy::class);
    }
}
