<?php
declare(strict_types=1);

namespace App\Core\Upload\Http\Resources;

use App\Core\Upload\Models\ChunkedUpload;
use App\Http\Resources\JsonResource;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * @property ChunkedUpload $resource
 */
#[OA\Schema(
    properties: [
        new OA\Property(property: 'uuid', type: 'string', format: 'uuid'),
        new OA\Property(property: 'original_name', type: 'string'),
        new OA\Property(property: 'mime_type', type: 'string', nullable: true),
        new OA\Property(property: 'total_size', type: 'integer', format: 'int64'),
        new OA\Property(property: 'received_bytes', type: 'integer', format: 'int64'),
        new OA\Property(property: 'created_at', type: 'number', nullable: true),
    ]
)]
class ChunkedUploadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->whenHas('uuid'),
            'original_name' => $this->whenHas('original_name'),
            'mime_type' => $this->whenHas('mime_type'),
            'total_size' => $this->whenHas('total_size'),
            'received_bytes' => $this->whenHas('received_bytes'),
            'created_at' => $this->whenHasToTimestamp('created_at'),
        ];
    }
}
