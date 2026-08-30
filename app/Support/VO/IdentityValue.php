<?php
declare(strict_types=1);

namespace App\Support\VO;

use App\Core\AppIdentity;

/**
 * Значение, которое строится из личности актора.
 *
 * Парный к {@see StringValue} интерфейс для VO вроде PostAuthor: в Data-объект
 * приходит пользователь (например, из FillFromAuthenticatedUser), а домен хочет
 * видеть свой тип.
 */
interface IdentityValue
{
    public function __construct(AppIdentity $identity);
}
