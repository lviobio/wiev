<?php
declare(strict_types=1);

namespace App\Modules\Post\Actions\RemoveCover;

use App\Models\User;
use App\Modules\Post\VO\PostIdentifier;
use Spatie\LaravelData\Data;

class RemoveCoverData extends Data
{
    public function __construct(
        public User           $actorUser,
        public PostIdentifier $id,
    )
    {
    }
}
