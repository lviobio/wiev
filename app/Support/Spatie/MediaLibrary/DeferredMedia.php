<?php
declare(strict_types=1);

namespace App\Support\Spatie\MediaLibrary;

use App\Core\ModelManager\ModelManagerContract;
use App\Support\VO\FileValue;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\FileAdder;

/**
 * Операции над медиа, согласованные с ModelManager.
 *
 * Добавление откладывает сам {@see DeferredFileAdder}, поэтому здесь только то,
 * что через него не проходит: разворот доменного значения файла и очистка
 * коллекции.
 */
readonly class DeferredMedia
{
    public function __construct(
        private ModelManagerContract $modelManager,
    )
    {
    }

    /**
     * Медиа из доменного значения.
     *
     * Значение нельзя передать в addMedia() напрямую — он типизирован как
     * string|UploadedFile. Заодно восстанавливается исходное имя файла: из
     * временного пути media library вывела бы имя вида "phpA1B2C3".
     */
    public function add(HasMedia $model, FileValue $file): FileAdder
    {
        return $model
            ->addMedia($file->path)
            ->usingFileName($file->originalName)
            ->usingName($file->nameWithoutExtension());
    }

    /**
     * Очистка коллекции.
     *
     * Для управляемой модели — после коммита, по той же причине, по которой
     * откладывается запись: удаление файлов необратимо, а сама очистка
     * идёт мимо FileAdder и потому требует явного вызова.
     */
    public function clear(HasMedia $model, string $collectionName): void
    {
        if ($model instanceof Model && $this->modelManager->isManaged($model)) {
            $this->modelManager->afterFlush(
                static fn() => $model->clearMediaCollection($collectionName),
            );

            return;
        }

        $model->clearMediaCollection($collectionName);
    }
}
