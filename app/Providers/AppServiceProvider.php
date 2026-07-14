<?php
declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Support\Routing\DataFillingControllerDispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Routing\Contracts\ControllerDispatcher;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ControllerDispatcher::class, DataFillingControllerDispatcher::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict();

        Relation::enforceMorphMap([
            'user' => User::class,
        ]);
    }
}
