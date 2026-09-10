<?php
declare(strict_types=1);

namespace App\Modules\Post\VO;

use App\Core\VO\FileValue;
use App\Support\Validation\FileRule;

/**
 * Файл, прикрепляемый к посту.
 *
 * В отличие от обложки не сужен до изображений — принимается что угодно
 * в пределах лимита media library.
 */
readonly class PostFile extends FileValue
{
    public static function rules(): array
    {
        return FileRule::make()->toArray();
    }
}
