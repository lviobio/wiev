<?php
declare(strict_types=1);

namespace App\Core\VO;

/**
 * Файл в виде значения — то, что домен получает вместо Symfony/Illuminate типов.
 *
 * Здесь только то, что есть у любого файла: имя, тип, размер. Где файл лежит —
 * знает наследник, и это два принципиально разных случая:
 *
 *  - {@see UploadedFileValue} — байты пришли в этом же запросе (multipart). Живёт
 *    ровно до конца запроса, как и временный файл PHP под ним.
 *  - {@see StoredFileValue} — файл уже лежит на диске Laravel (локальном, S3, любом),
 *    и запрос ссылается на него идентификатором. Именно так файлы попадают в домен
 *    после того, как клиент загрузил их заранее во временное хранилище.
 *
 * Преобразование из сырого входа живёт на границе — в кастах laravel-data
 * ({@see \App\Support\Spatie\Data\UploadedFileValueCast},
 * {@see \App\Support\Spatie\Data\StoredFileValueCast}), ровно как у
 * {@see NumberIdentifier} с параметром маршрута.
 */
abstract readonly class FileValue implements HasValidationRules
{
    public function __construct(
        public string $originalName,
        public string $mimeType,
        public int    $size,
    )
    {
    }

    public function nameWithoutExtension(): string
    {
        return pathinfo($this->originalName, PATHINFO_FILENAME);
    }
}
