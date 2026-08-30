<?php
declare(strict_types=1);

namespace App\Modules\Post\Domain\VO;

use App\Support\VO\ValidatedStringValue;

final readonly class PostTitle extends ValidatedStringValue
{
    public static function rules(): array
    {
        return ['string', 'min:3', 'max:255'];
    }
}
