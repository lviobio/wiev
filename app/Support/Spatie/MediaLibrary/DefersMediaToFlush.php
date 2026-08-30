<?php
declare(strict_types=1);

namespace App\Support\Spatie\MediaLibrary;

use Closure;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use Spatie\MediaLibrary\MediaCollections\FileAdder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Мост между {@see DeferredFileAdder} и ModelManager.
 *
 * ModelManager::persistModel() зовёт pullPerformOnSaved() до save(), а исполняет
 * полученные колбэки после коммита. Это единственная правильная точка для медиа:
 * файлы на диск пишутся и удаляются вне транзакции и откатом не восстанавливаются,
 * поэтому запись медиа внутри транзакции при откате оставила бы строку media,
 * указывающую на уже стёртый файл (коллекция singleFile сносит предыдущий файл
 * немедленно).
 */
trait DefersMediaToFlush
{
    /**
     * Коллекции, которые нужно очистить при flush(). Храним имена, а не колбэки:
     * модель обязана оставаться сериализуемой.
     *
     * @var list<string>
     */
    protected array $mediaCollectionsToClearOnFlush = [];

    /**
     * Отложенный аналог clearMediaCollection().
     */
    public function clearMediaCollectionOnFlush(string $collectionName): static
    {
        $this->mediaCollectionsToClearOnFlush[] = $collectionName;

        // Страховка на случай save() мимо менеджера — тем же приёмом, каким
        // media library подстраховывает несохранённые модели.
        static::saved(static function (Model $model): void {
            if (method_exists($model, 'performQueuedMediaClears')) {
                $model->performQueuedMediaClears();
            }
        });

        return $this;
    }

    /**
     * Вызывается ModelManager'ом перед save(); возвращённое исполняется после коммита.
     *
     * @return list<Closure>
     */
    public function pullPerformOnSaved(): array
    {
        $callbacks = [];

        foreach ($this->pullMediaCollectionsToClear() as $collectionName) {
            $callbacks[] = fn() => $this->clearMediaCollection($collectionName);
        }

        $this->processUnattachedMedia(function (Media $media, FileAdder $fileAdder) use (&$callbacks): void {
            if (!$fileAdder instanceof DeferredFileAdder) {
                throw new LogicException(sprintf(
                    'Cannot defer media of %s: expected %s, got %s. Is the container binding in place?',
                    static::class,
                    DeferredFileAdder::class,
                    $fileAdder::class,
                ));
            }

            $callbacks[] = fn() => $fileAdder->attachNow($this, $media);
        });

        return $callbacks;
    }

    /**
     * Точка для страховочного листенера: очистить всё, что не забрал менеджер.
     */
    public function performQueuedMediaClears(): void
    {
        foreach ($this->pullMediaCollectionsToClear() as $collectionName) {
            $this->clearMediaCollection($collectionName);
        }
    }

    /**
     * @return list<string>
     */
    private function pullMediaCollectionsToClear(): array
    {
        $collections = $this->mediaCollectionsToClearOnFlush;

        $this->mediaCollectionsToClearOnFlush = [];

        return $collections;
    }
}
