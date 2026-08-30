<?php
declare(strict_types=1);

namespace App\Support\VO;

/**
 * Значение, которое строится из строки.
 *
 * Нужен только затем, чтобы каст laravel-data мог опознать такие VO по типу
 * свойства и собрать их сам: глобальные касты резолвятся в том числе по
 * интерфейсам ({@see \App\Support\Spatie\Data\StringValueCast}).
 *
 * Проверки инварианта остаются в конструкторе конкретного VO — интерфейс
 * говорит только о том, как его собрать.
 */
interface StringValue
{
    public function __construct(string $value);
}
