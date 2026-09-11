<?php
declare(strict_types=1);

namespace App\Core\VO;

use App\Core\Upload\Rules\TemporaryUploadRule;

/**
 * {@see StoredFileValue}, суженный до изображений.
 *
 * Проверяется mime-тип, записанный при приёме файла во временное хранилище — его
 * определял сервер по содержимому ({@see \Illuminate\Http\UploadedFile::getMimeType()}),
 * а не клиент, так что доверять ему можно.
 */
abstract readonly class ImageFileValue extends StoredFileValue
{
    public static function rules(): array
    {
        return [TemporaryUploadRule::make()->image()];
    }
}
