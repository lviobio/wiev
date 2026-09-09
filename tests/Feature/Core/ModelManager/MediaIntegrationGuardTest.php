<?php
declare(strict_types=1);

namespace Tests\Feature\Core\ModelManager;

use App\Core\ModelManager\Guards\MissingMediaIntegrationException;
use App\Core\ModelManager\ModelManagerContract;
use App\Modules\Post\Models\Post;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/** Медиа-модель, подключившая родной трейт media library вместо трейта менеджера. */
class UnmanageableMediaModel extends Model implements HasMedia
{
    use InteractsWithMedia;
}

/** Модель вообще без медиа — проверка её не касается. */
class PlainModel extends Model
{
}

beforeEach(function () {
    $this->manager = resolve(ModelManagerContract::class);
});

test('the manager refuses a model that bypasses its media integration', function () {
    expect(fn() => $this->manager->persist(new UnmanageableMediaModel))
        ->toThrow(
            MissingMediaIntegrationException::class,
            'Replace that trait with App\Core\ModelManager\InteractsWithMedia.',
        );
});

test('a model wired through the manager trait is accepted', function () {
    $model = Post::factory()->create();

    $this->manager->persist($model);

    expect($this->manager->isManaged($model))->toBeTrue();
});

test('a model without media is none of the check business', function () {
    $this->manager->persist(new PlainModel);

    expect(true)->toBeTrue();
});
