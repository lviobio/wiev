<?php
declare(strict_types=1);

namespace App\Core\ModelManager;

use App\Core\VO\FileValue;
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
     * Значение разворачивается в путь плюс исходное имя: из временного пути
     * media library вывела бы имя вида "phpA1B2C3".
     */
    public function addMedia(string|UploadedFile|FileValue $file): FileAdder
    {
        if (!$file instanceof FileValue) {
            return $this->addMediaThroughMediaLibrary($file);
        }

        return $this
            ->addMediaThroughMediaLibrary($file->path)
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
