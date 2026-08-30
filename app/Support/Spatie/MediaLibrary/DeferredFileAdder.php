<?php
declare(strict_types=1);

namespace App\Support\Spatie\MediaLibrary;

use App\Core\ModelManager\ModelManagerContract;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\FileAdder;
use Spatie\MediaLibrary\MediaCollections\Filesystem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * FileAdder, откладывающий привязку медиа до flush() менеджера.
 *
 * Механизм отложенной привязки в media library уже есть — очередь
 * prepareToAttachMedia()/processUnattachedMedia() на модели. Оригинальный
 * FileAdder включает её только для несохранённой модели; здесь она включается
 * ещё и для управляемой менеджером, а выгребает очередь {@see DefersMediaToFlush}.
 *
 * Семантика для всех остальных моделей не меняется: фабрики, сидеры и любой код
 * мимо менеджера как писали медиа немедленно, так и пишут.
 */
class DeferredFileAdder extends FileAdder
{
    public function __construct(
        ?Filesystem                          $filesystem,
        private readonly ModelManagerContract $modelManager,
    )
    {
        parent::__construct($filesystem);
    }

    /**
     * Точка исполнения отложенной привязки: processMediaItem() protected,
     * а дренаж очереди живёт на модели.
     */
    public function attachNow(HasMedia $model, Media $media): void
    {
        $this->processMediaItem($model, $media, $this);
    }

    protected function attachMedia(Media $media): void
    {
        // Несохранённая модель: media library и так откладывает до created.
        // Оставляем её листенер страховкой на случай save() мимо менеджера —
        // управляемую модель менеджер выгребет раньше, чем листенер сработает,
        // и тому достанется пустая очередь.
        if (!$this->subject->exists) {
            parent::attachMedia($media);

            return;
        }

        // Существующая, но не управляемая модель — пишем сразу, как раньше.
        if (!$this->modelManager->isManaged($this->subject)) {
            parent::attachMedia($media);

            return;
        }

        $this->subject->prepareToAttachMedia($media, $this);
    }
}
