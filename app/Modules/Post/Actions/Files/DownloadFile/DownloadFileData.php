<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\Files\DownloadFile;

use App\Modules\Post\Domain\VO\PostFileIdentifier;
use App\Modules\Post\Domain\VO\PostIdentifier;
use Spatie\LaravelData\Data;

class DownloadFileData extends Data
{
    public function __construct(
        public PostIdentifier     $id,
        public PostFileIdentifier $fileId,
    )
    {
    }
}
