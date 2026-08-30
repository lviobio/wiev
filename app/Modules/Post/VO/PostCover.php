<?php
declare(strict_types=1);

namespace App\Modules\Post\VO;

use App\Support\Validation\ImageRule;
use App\Support\VO\FileValue;

readonly class PostCover extends FileValue
{
    public static function rules(): array
    {
        return ImageRule::make()->toArray();
    }
}
