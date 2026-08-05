<?php
declare(strict_types=1);

namespace App\Modules\Post\Http\Resources;

use App\Http\Resources\JsonResource;
use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\Models\Post;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * @property Post $resource
 */
#[OA\Schema(
    properties: [
        new OA\Property(property: 'id', type: 'number'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'content', type: 'string', nullable: true),
        new OA\Property(property: 'cover', type: 'string', nullable: true),
        new OA\Property(property: 'deleted_at', type: 'number', nullable: true),
        new OA\Property(property: 'created_at', type: 'number', nullable: true),
        new OA\Property(property: 'updated_at', type: 'number', nullable: true),
    ]
)]
class PostResource extends JsonResource
{
    public static function eagerLoads(): array
    {
        return [
            'authorUser',
            'media' => fn($q) => $q->whereCollectionName(PostMediaCollectionEnum::Cover->value),
        ];
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->whenHas('id'),
            'title' => $this->whenHas('title'),
            'content' => $this->whenHas('content'),
            'cover' => $this->whenHasMediaToUrl(PostMediaCollectionEnum::Cover->value),
            $this->mergeWhenHasDeletedAt(),
            $this->mergeWhenHasTimestamps(),
        ];
    }
}
