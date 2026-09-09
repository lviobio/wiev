<?php
declare(strict_types=1);

namespace App\Core\ModelManager\Guards;

use App\Core\ModelManager\InteractsWithMedia;
use Spatie\MediaLibrary\InteractsWithMedia as MediaLibraryInteractsWithMedia;

/**
 * Модель с медиа обязана подключать {@see InteractsWithMedia} вместо родного
 * трейта media library.
 *
 * Иначе clearMediaCollection() и addMedia() пройдут мимо менеджера и запишут
 * файлы внутри транзакции, откатить которые нельзя.
 */
class MediaLibraryIntegrationGuard implements ManagedModelGuard
{
    public function guard(string $modelClass): void
    {
        $traits = class_uses_recursive($modelClass);

        if (!isset($traits[MediaLibraryInteractsWithMedia::class])) {
            return;
        }

        if (isset($traits[InteractsWithMedia::class])) {
            return;
        }

        throw MissingMediaIntegrationException::make($modelClass);
    }
}
