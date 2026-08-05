<?php
declare(strict_types=1);

namespace App\Http\Resources;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Resources\Json\JsonResource as BaseJsonResource;
use Illuminate\Http\Resources\MergeValue;
use Illuminate\Http\Resources\MissingValue;
use Spatie\MediaLibrary\HasMedia;

/**
 * @property Model|SoftDeletes $resource
 */
abstract class JsonResource extends BaseJsonResource
{
    /**
     * Relations this resource reads while serialising.
     *
     * Declared here rather than in the controller so the eager-load list lives next
     * to the code that consumes it, and so the generated HTTP layer does not have to
     * express constrained loads - which are closures and cannot be code-generated.
     *
     * @return array<int|string, mixed>
     */
    public static function eagerLoads(): array
    {
        return [];
    }

    /**
     * Wrap a model, loading whatever {@see static::eagerLoads()} declares.
     */
    public static function loaded(Model $model): static
    {
        $relations = static::eagerLoads();

        return static::make($relations === [] ? $model : $model->load($relations));
    }

    protected function whenHasToTimestamp(string $attribute): mixed
    {
        return $this->whenHas(
            attribute: $attribute,
            value: $this->castToTimestamp(...),
        );
    }

    protected function castToTimestamp(?CarbonInterface $value): ?int
    {
        return $value?->getTimestampMs();
    }

    protected function mergeWhenHasIsTrashed(): MergeValue
    {
        return $this->mergeWhen(
            condition: $this->resource->hasAttribute('deleted_at'),
            value: fn() => [
                'is_trashed' => $this->resource->trashed(),
            ],
        );
    }

    protected function mergeWhenHasDeletedAt(): MergeValue
    {
        return $this->mergeWhen(
            condition: $this->resource->hasAttribute('deleted_at'),
            value: fn() => [
                'deleted_at' => $this->castToTimestamp($this->resource->getAttribute('deleted_at')),
            ],
        );
    }

    protected function mergeWhenHasTimestamps(): MergeValue
    {
        return $this->merge([
            'created_at' => $this->whenHasToTimestamp('created_at'),
            'updated_at' => $this->whenHasToTimestamp('updated_at'),
        ]);
    }

    protected function whenHasMediaToUrl(string $collection = 'default'): mixed
    {
        if (!$this->resource instanceof HasMedia) {
            return new MissingValue;
        }

        $media = $this->resource->getMedia($collection);

        if ($media->isNotEmpty()) {
            return $media->first()->getUrl();
        }

        return new MissingValue;
    }
}
