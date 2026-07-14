<?php
declare(strict_types=1);

namespace App\Modules\Post\Providers;

use App\Modules\Post\Models\Post;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class PostModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Relation::morphMap([
            'post' => Post::class,
        ]);
    }
}
