<?php
declare(strict_types=1);

namespace App\Core\VO;

use Illuminate\Http\UploadedFile;

/**
 * Файл, байты которого пришли в этом же запросе (multipart).
 *
 * Единственное место, где такое значение нужно, — приём файла во временное
 * хранилище ({@see \App\Core\Upload\VO\NewUpload}); дальше в домен файлы попадают
 * уже как {@see StoredFileValue}. Для генератора HTTP-слоя наличие такого свойства
 * в Data-объекте означает, что эндпоинт говорит на multipart/form-data.
 *
 * Значение живёт ровно до конца запроса: PHP удаляет временный файл загрузки при
 * завершении, а Illuminate\Http\Testing\File — ещё раньше, при сборке мусора своего
 * объекта. Ссылка на источник привязывает время жизни файла к значению.
 */
abstract readonly class UploadedFileValue extends FileValue
{
    public function __construct(
        string              $originalName,
        string              $mimeType,
        int                 $size,
        public UploadedFile $source,
    )
    {
        parent::__construct($originalName, $mimeType, $size);
    }

    /**
     * Форма сырого входа. Наследник сужает: изображение, документ, размер.
     *
     * @return list<mixed>
     */
    public static function rules(): array
    {
        return ['file'];
    }

    public static function fromUploadedFile(UploadedFile $file): static
    {
        return new static(
            originalName: $file->getClientOriginalName(),
            mimeType: $file->getMimeType() ?? 'application/octet-stream',
            size: $file->getSize() ?: 0,
            source: $file,
        );
    }
}
