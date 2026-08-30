<?php
declare(strict_types=1);

namespace App\Core\ModelManager;

/**
 * Что делать с записью, которая была в загруженной связи при снятии снапшота,
 * но отсутствует в коллекции на момент flush().
 *
 * Аналог orphanRemoval в Doctrine, но с явным разделением delete/nullify:
 * в Eloquent нет метаданных о nullable-ости FK, поэтому решение принимает модель.
 */
enum OrphanRemovalStrategy: string
{
    /** Удалить строку (с учётом SoftDeletes, если трейт подключён). */
    case Delete = 'delete';

    /** Оставить строку, занулив внешний ключ (и morph type для morph-связей). */
    case Nullify = 'nullify';

    /** Ничего не делать. Значение по умолчанию для всех связей. */
    case Ignore = 'ignore';
}
