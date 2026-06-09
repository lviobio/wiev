<?php
declare(strict_types=1);

namespace App\Modules\Post\Enums;

enum PostMediaCollectionEnum: string
{
    case Cover = 'cover';
    case CoverConversionThumb = 'thumb';
}
