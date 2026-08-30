<?php
declare(strict_types=1);

namespace App\Modules\Post\Domain\VO;

use App\Support\VO\ValidatedStringValue;

final readonly class PostContent extends ValidatedStringValue
{
    public static function rules(): array
    {
        return ['string', 'max:65535'];
    }
}
