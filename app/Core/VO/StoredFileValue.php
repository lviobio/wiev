<?php
declare(strict_types=1);

namespace App\Core\VO;

use App\Core\Upload\Rules\TemporaryUploadRule;

/**
 * Файл, который уже лежит на диске Laravel — локальном, S3, любом.
 *
 * Адресуется парой disk + path, а не путём в файловой системе: временные загрузки
 * и медиа могут жить на разных дисках (даже в разных бакетах), и ничего здесь не
 * должно предполагать, что файл можно открыть через SplFileInfo. Переносит его в
 * media library {@see \App\Core\ModelManager\InteractsWithMedia::addMedia()} —
 * через `addMediaFromDisk()`, которому всё равно, откуда копировать.
 *
 * В запросе такое значение приходит идентификатором временной загрузки
 * ({@see \App\Core\Upload\Models\TemporaryUpload}) — единственный способ, которым
 * клиент может сослаться на уже лежащий на диске файл. Отсюда правило по умолчанию;
 * наследник сужает его: {@see ImageFileValue}, ограничение размера и т.п.
 */
abstract readonly class StoredFileValue extends FileValue
{
    public function __construct(
        string        $originalName,
        string        $mimeType,
        int           $size,
        public string $disk,
        public string $path,
    )
    {
        parent::__construct($originalName, $mimeType, $size);
    }

    /**
     * @return list<mixed>
     */
    public static function rules(): array
    {
        return [TemporaryUploadRule::make()];
    }
}
