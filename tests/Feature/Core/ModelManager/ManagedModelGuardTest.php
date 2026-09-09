<?php
declare(strict_types=1);

namespace Tests\Feature\Core\ModelManager;

use App\Core\ModelManager\Guards\ManagedModelGuard;
use App\Core\ModelManager\Guards\MediaLibraryIntegrationGuard;
use App\Core\ModelManager\ModelManager;
use App\Core\ModelManager\ModelManagerContract;
use App\Modules\Post\Models\Post;
use LogicException;

class RecordingGuard implements ManagedModelGuard
{
    /** @var list<class-string> */
    public array $checked = [];

    public function guard(string $modelClass): void
    {
        $this->checked[] = $modelClass;
    }
}

class RejectingGuard implements ManagedModelGuard
{
    public function guard(string $modelClass): void
    {
        throw new LogicException('This model is not welcome here.');
    }
}

test('a registered guard checks every class taken under management', function () {
    $guard = new RecordingGuard;

    new ModelManager([$guard])->persist(Post::factory()->create());

    expect($guard->checked)->toContain(Post::class);
});

test('a guard runs once per class, not once per instance', function () {
    $guard = new RecordingGuard;
    $manager = new ModelManager([$guard]);

    $manager->persist(Post::factory()->create());
    $manager->persist(Post::factory()->create());

    $posts = array_filter($guard->checked, static fn(string $class): bool => $class === Post::class);

    expect($posts)->toHaveCount(1);
});

test('a failing guard keeps the model out of the manager', function () {
    $manager = new ModelManager([new RejectingGuard]);
    $model = Post::factory()->create();

    expect(fn() => $manager->persist($model))->toThrow(LogicException::class)
        ->and($manager->isManaged($model))->toBeFalse();
});

test('the manager resolved from the container carries the configured guards', function () {
    expect(config('model-manager.guards'))->toContain(MediaLibraryIntegrationGuard::class)
        ->and(resolve(ModelManagerContract::class))->toBeInstanceOf(ModelManager::class);
});
