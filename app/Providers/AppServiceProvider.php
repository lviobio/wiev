<?php
declare(strict_types=1);

namespace App\Providers;

use App\Core\ModelManager\ModelManager;
use App\Core\ModelManager\ModelManagerContract;
use App\Models\User;
use App\Support\Routing\AppControllerDispatcher;
use App\Support\Spatie\MediaLibrary\DeferredFileAdder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Routing\Contracts\ControllerDispatcher;
use Spatie\MediaLibrary\MediaCollections\FileAdder;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ControllerDispatcher::class, AppControllerDispatcher::class);

        // Unit of Work живёт ровно один запрос / одну джобу: identity map и
        // снапшоты связей не должны переживать своё окружение.
        $this->app->scoped(ModelManagerContract::class, ModelManager::class);

        // Медиа управляемых менеджером моделей пишется на flush(), а не сразу.
        // FileAdderFactory резолвит адаптер через контейнер, так что перебинд
        // покрывает все входы: addMedia(), addMediaFromDisk(), addMediaFromRequest().
        $this->app->bind(FileAdder::class, DeferredFileAdder::class);
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
