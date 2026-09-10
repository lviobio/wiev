<?php
declare(strict_types=1);

namespace App\Modules\Post\Http\Resources;

use App\Http\Resources\JsonResource;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property Media $resource
 */
#[OA\Schema(
    properties: [
        new OA\Property(property: 'uuid', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'file_name', type: 'string'),
        new OA\Property(property: 'mime_type', type: 'string', nullable: true),
        new OA\Property(property: 'size', type: 'integer', format: 'int64'),
        new OA\Property(property: 'url', type: 'string'),
        new OA\Property(property: 'created_at', type: 'number', nullable: true),
        new OA\Property(property: 'updated_at', type: 'number', nullable: true),
    ]
)]
class PostFileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->whenHas('uuid'),
            'name' => $this->whenHas('name'),
            'file_name' => $this->whenHas('file_name'),
            'mime_type' => $this->whenHas('mime_type'),
            'size' => $this->whenHas('size'),
            'url' => $this->resource->getUrl(),
            $this->mergeWhenHasTimestamps(),
        ];
    }
}
