<?php
declare(strict_types=1);

namespace App\Core\Upload\Actions\Chunked\ShowChunkedUpload;

use App\Core\Upload\Models\ChunkedUpload;

/**
 * Status lookup for a session in progress - lets the client resume after a reload by
 * polling `received_bytes` before deciding where to continue from.
 */
readonly class ShowChunkedUploadAction
{
    public function __invoke(ShowChunkedUploadData $data): ChunkedUpload
    {
        return ChunkedUpload::query()
            ->ownedBy($data->actorUser->getKey())
            ->where('uuid', $data->id->value)
            ->firstOrFail();
    }
}
