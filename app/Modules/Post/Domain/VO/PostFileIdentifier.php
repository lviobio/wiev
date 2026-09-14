<?php
declare(strict_types=1);

namespace App\Modules\Post\Domain\VO;

use App\Core\VO\UuidIdentifier;

/**
 * Файл адресуется uuid из media, а не числовым id: перебирать чужие вложения
 * по возрастающему ключу не должно быть возможно.
 */
readonly class PostFileIdentifier extends UuidIdentifier
{
}
