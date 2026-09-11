<?php
declare(strict_types=1);

namespace App\Core\Upload\Http\Resources;

use App\Core\Upload\Models\TemporaryUpload;
use App\Http\Resources\JsonResource;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * @property TemporaryUpload $resource
 */
#[OA\Schema(
    properties: [
        new OA\Property(property: 'uuid', type: 'string', format: 'uuid'),
        new OA\Property(property: 'original_name', type: 'string'),
        new OA\Property(property: 'mime_type', type: 'string', nullable: true),
        new OA\Property(property: 'size', type: 'integer', format: 'int64'),
        new OA\Property(property: 'created_at', type: 'number', nullable: true),
    ]
)]
class TemporaryUploadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->whenHas('uuid'),
            'original_name' => $this->whenHas('original_name'),
            'mime_type' => $this->whenHas('mime_type'),
            'size' => $this->whenHas('size'),
            'created_at' => $this->whenHasToTimestamp('created_at'),
        ];
    }
}
