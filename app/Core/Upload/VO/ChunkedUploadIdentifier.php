<?php
declare(strict_types=1);

namespace App\Core\Upload\VO;

use App\Core\VO\UuidIdentifier;

/**
 * Mirrors {@see \App\Modules\Post\VO\PostFileIdentifier}: a chunked-upload session is
 * addressed by its uuid, not a sequential id, for the same reason.
 */
readonly class ChunkedUploadIdentifier extends UuidIdentifier
{
}
