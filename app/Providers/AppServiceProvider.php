<?php
declare(strict_types=1);

namespace App\Providers;

use App\Core\ModelManager\Guards\ManagedModelGuard;
use App\Core\ModelManager\ModelManager;
use App\Core\ModelManager\ModelManagerContract;
use App\Core\Upload\Models\ChunkedUpload;
use App\Core\Upload\Models\TemporaryUpload;
use App\Models\User;
use App\Support\Routing\AppControllerDispatcher;
use App\Support\Spatie\MediaLibrary\DeferredFileAdder;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Routing\Contracts\ControllerDispatcher;
use App\Models\Media;
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

        // Проверки пригодности моделей объявляются конфигом, а не менеджером:
        // интеграция с новым пакетом добавляется строкой в config/model-manager.php.
        $this->app->bind(ModelManager::class, static fn(Application $app): ModelManager => new ModelManager(
            array_map(
                static fn(string $guard): ManagedModelGuard => $app->make($guard),
                config('model-manager.guards', []),
            ),
        ));

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
            'media' => Media::class,
            // Not morphed against anything themselves - registered so getMorphClass()
            // doesn't throw when bootstrap/app.php's generic ModelNotFoundException
            // renderable (the 424 path every "id not found" endpoint goes through)
            // reads the model off a findOrFail() failure for either of them.
            'temporary_upload' => TemporaryUpload::class,
            'chunked_upload' => ChunkedUpload::class,
        ]);
    }
}
