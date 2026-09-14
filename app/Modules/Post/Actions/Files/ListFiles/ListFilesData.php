<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\Files\ListFiles;

use App\Modules\Post\Domain\VO\PostIdentifier;
use Spatie\LaravelData\Data;

class ListFilesData extends Data
{
    public function __construct(
        public PostIdentifier $id,
    )
    {
    }
}
