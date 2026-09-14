<?php
declare(strict_types=1);

namespace App\Modules\Post\Domain\VO;

use App\Core\VO\StoredFileValue;

/**
 * Файл, прикрепляемый к посту.
 *
 * В отличие от обложки не сужен до изображений — принимается что угодно
 * в пределах лимита media library, проверенного при загрузке во временное
 * хранилище ({@see \App\Core\Upload\VO\NewUpload}).
 */
readonly class PostFile extends StoredFileValue
{
}
