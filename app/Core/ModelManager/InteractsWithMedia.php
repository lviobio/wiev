<?php
declare(strict_types=1);

namespace App\Core\ModelManager;

use App\Core\VO\FileValue;
use App\Core\VO\StoredFileValue;
use App\Core\VO\UploadedFileValue;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia as MediaLibraryInteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\FileAdder;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Замена родного трейта media library для моделей, живущих под ModelManager.
 *
 * Запись медиа откладывает {@see \App\Support\Spatie\MediaLibrary\DeferredFileAdder}:
 * он перехватывает единственную точку, через которую media library пишет файл.
 * Мимо него идут два метода самой модели, они и переопределены здесь.
 *
 * Подключать вместо `Spatie\MediaLibrary\InteractsWithMedia` — менеджер
 * проверяет это при взятии модели под управление и иначе отказывается работать.
 */
trait InteractsWithMedia
{
    use MediaLibraryInteractsWithMedia {
        addMedia as private addMediaThroughMediaLibrary;
        clearMediaCollection as private clearMediaCollectionThroughMediaLibrary;
    }

    /**
     * Кроме исходных типов принимает доменное значение файла.
     *
     * Файл на диске ({@see StoredFileValue}) уходит через `addMediaFromDisk()`: media
     * library сама копирует его с любого диска на диск медиа — временное хранилище
     * и медиа могут жить где угодно, хоть в разных бакетах. Удалять ли оригинал,
     * решает {@see \App\Support\Spatie\MediaLibrary\DeferredFileAdder}.
     * Файл из запроса ({@see UploadedFileValue}) — как обычный UploadedFile.
     *
     * Имя в обоих случаях берётся из значения: иначе media library вывела бы его
     * из пути на диске (uuid-каталог, phpA1B2C3 и т.п.).
     */
    public function addMedia(string|UploadedFile|FileValue $file): FileAdder
    {
        $adder = match (true) {
            $file instanceof StoredFileValue => $this->addMediaFromDisk($file->path, $file->disk),
            $file instanceof UploadedFileValue => $this->addMediaThroughMediaLibrary($file->source),
            default => $this->addMediaThroughMediaLibrary($file),
        };

        if (!$file instanceof FileValue) {
            return $adder;
        }

        return $adder
            ->usingFileName($file->originalName)
            ->usingName($file->nameWithoutExtension());
    }

    /**
     * Для управляемой модели очистка выполняется после коммита flush().
     *
     * Причина та же, по которой откладывается запись: файлы удаляются с диска
     * немедленно и необратимо, откат транзакции их не вернёт — строка media
     * восстановилась бы, указывая в никуда.
     */
    public function clearMediaCollection(string $collectionName = 'default'): HasMedia
    {
        $modelManager = app(ModelManagerContract::class);

        if (!$modelManager->isManaged($this)) {
            return $this->clearMediaCollectionThroughMediaLibrary($collectionName);
        }

        $modelManager->afterFlush(
            fn() => $this->clearMediaCollectionThroughMediaLibrary($collectionName),
        );

        return $this;
    }
}
