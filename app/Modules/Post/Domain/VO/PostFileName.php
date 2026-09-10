<?php
declare(strict_types=1);

namespace App\Modules\Post\Domain\VO;

use App\Core\VO\ValidatedStringValue;

/**
 * Отображаемое имя файла — то, что видит человек. Имя файла на диске
 * media library держит отдельно и оно не меняется.
 */
final readonly class PostFileName extends ValidatedStringValue
{
    public static function rules(): array
    {
        return ['string', 'max:255'];
    }
}
