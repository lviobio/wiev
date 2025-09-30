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
