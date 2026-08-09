<?php
declare(strict_types=1);

namespace App\Enums;

enum AuthAbilityEnum: string
{
    case Access = 'access';
    case Manage = 'manage';
}
