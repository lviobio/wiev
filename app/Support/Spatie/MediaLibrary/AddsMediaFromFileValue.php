<?php
declare(strict_types=1);

namespace App\Support\Spatie\MediaLibrary;

use App\Support\VO\FileValue;
use Spatie\MediaLibrary\MediaCollections\FileAdder;

/**
 * Переходник между доменным значением файла и media library.
 *
 * addMedia() типизирован как string|UploadedFile, поэтому значение
 * разворачивается здесь: путь плюс исходное имя, которое иначе потерялось бы —
 * из временного пути media library вывела бы имя вида "phpA1B2C3".
 */
trait AddsMediaFromFileValue
{
    public function addMediaFromValue(FileValue $file): FileAdder
    {
        return $this
            ->addMedia($file->path)
            ->usingFileName($file->originalName)
            ->usingName($file->nameWithoutExtension());
    }
}
