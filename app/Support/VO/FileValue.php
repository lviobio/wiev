<?php
declare(strict_types=1);

namespace App\Support\VO;

use Illuminate\Http\UploadedFile;

/**
 * Файл, пришедший в приложение, в виде значения.
 *
 * Домен не должен видеть Symfony/Illuminate типы: экшены и use case'ы получают
 * наследника этого класса, а не UploadedFile. Преобразование живёт на границе —
 * в касте laravel-data ({@see \App\Support\Spatie\Data\FileValueCast}), ровно
 * как у {@see NumberIdentifier} с параметром маршрута.
 *
 * Сейчас значение указывает на временный файл запроса, поэтому оно живёт ровно
 * до конца запроса. Если понадобится переживать процесс (очередь, многошаговая
 * форма) — наследник должен ссылаться на файл, уже перенесённый на диск.
 */
abstract readonly class FileValue implements HasValidationRules
{
    public function __construct(
        public string          $path,
        public string          $originalName,
        public string          $mimeType,
        public int             $size,

        /**
         * Источник держится живым намеренно: путь сам по себе ничего не
         * гарантирует. PHP удаляет временный файл загрузки в конце запроса,
         * а Illuminate\Http\Testing\File — ещё раньше, при сборке мусора
         * своего объекта. Ссылка привязывает время жизни файла к значению.
         */
        protected ?UploadedFile $source = null,
    )
    {
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
            path: $file->getPathname(),
            originalName: $file->getClientOriginalName(),
            mimeType: $file->getMimeType() ?? 'application/octet-stream',
            size: $file->getSize() ?: 0,
            source: $file,
        );
    }

    public function nameWithoutExtension(): string
    {
        return pathinfo($this->originalName, PATHINFO_FILENAME);
    }
}
