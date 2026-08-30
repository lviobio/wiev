<?php
declare(strict_types=1);

namespace App\Support\VO;

use DomainException;

/**
 * Нарушен инвариант значения. DomainException, а не ValidationException:
 * до конструктора VO значение доходит уже провалидированным на границе,
 * и попадание сюда означает ошибку в коде, а не в запросе.
 */
class InvalidValueException extends DomainException
{
    /**
     * @param class-string $valueClass
     * @param list<string> $errors
     */
    public static function make(string $valueClass, array $errors): self
    {
        return new self(sprintf(
            '%s: %s',
            class_basename($valueClass),
            implode(' ', $errors),
        ));
    }
}
