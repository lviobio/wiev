<?php
declare(strict_types=1);

namespace App\Core\Upload\Actions\StoreUpload;

use App\Core\Upload\VO\NewUpload;
use App\Models\User;
use Spatie\LaravelData\Data;

class StoreUploadData extends Data
{
    public function __construct(
        public NewUpload $file,

        public User      $actorUser,
    )
    {
    }
}
