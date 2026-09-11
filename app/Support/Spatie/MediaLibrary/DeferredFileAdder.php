<?php
declare(strict_types=1);

namespace App\Support\Spatie\MediaLibrary;

use App\Core\ModelManager\ModelManagerContract;
use App\Core\Upload\TemporaryUploadClaimer;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\FileAdder;
use Spatie\MediaLibrary\MediaCollections\Filesystem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\RemoteFile;

/**
 * FileAdder, откладывающий запись медиа управляемых моделей до коммита flush().
 *
 * Перехват стоит в одной точке — processMediaItem(). Через неё проходят оба
 * пути media library: для существующей модели её зовёт attachMedia() сразу,
 * для новой — листенер события created. Во втором случае к моменту вызова
 * модель уже сохранена менеджером, поэтому проверка isManaged() работает и там.
 *
 * Модели ничего не знают об этом: трейтов подмешивать не нужно, достаточно
 * штатного InteractsWithMedia. Для всего, что идёт мимо менеджера — фабрики,
 * сидеры, разовые скрипты — поведение остаётся прежним, запись немедленная.
 *
 * Откладывается именно на после коммита: файлы пишутся и удаляются вне
 * транзакции, и откат их не вернёт. Коллекция singleFile сносит предыдущий
 * файл сразу, поэтому запись медиа внутри транзакции при откате оставила бы
 * строку media, указывающую на уже стёртый файл.
 *
 * Файл с диска (RemoteFile — так приходят временные загрузки) перед этим
 * помечается использованным внутри транзакции, перед коммитом: см.
 * {@see TemporaryUploadClaimer}. Так «использован» означает ровно «изменения,
 * ради которых файл нужен, закоммичены», и ничего не нужно откатывать вручную.
 * Он же решает, переносить оригинал или копировать (`preservingOriginal()`) —
 * это свойство временной загрузки, а не всякого файла с диска.
 */
class DeferredFileAdder extends FileAdder
{
    public function __construct(
        ?Filesystem                            $filesystem,
        private readonly ModelManagerContract  $modelManager,
        private readonly TemporaryUploadClaimer $claimer,
    )
    {
        parent::__construct($filesystem);
    }

    protected function processMediaItem(HasMedia $model, Media $media, FileAdder $fileAdder): void
    {
        $file = $fileAdder->file;

        $claim = function () use ($file, $fileAdder): void {
            if ($file instanceof RemoteFile && $this->claimer->claim($file->getDisk(), $file->getKey())) {
                $fileAdder->preservingOriginal();
            }
        };

        if (!($model instanceof Model && $this->modelManager->isManaged($model))) {
            $claim();

            parent::processMediaItem($model, $media, $fileAdder);

            return;
        }

        $this->modelManager->beforeCommit($claim);

        $this->modelManager->afterFlush(
            fn() => parent::processMediaItem($model, $media, $fileAdder),
        );
    }
}
