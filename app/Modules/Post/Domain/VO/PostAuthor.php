<?php
declare(strict_types=1);

namespace App\Modules\Post\Domain\VO;

use App\Core\AppIdentity;
use App\Support\VO\IdentityValue;

final readonly class PostAuthor implements IdentityValue
{
    public function __construct(
        public AppIdentity $identity,
    )
    {
    }
}