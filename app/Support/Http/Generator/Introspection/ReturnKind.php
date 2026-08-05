<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\Introspection;

/**
 * What an Action hands back, which decides the controller's return type and the
 * shape of the documented success response.
 */
enum ReturnKind
{
    case Model;
    case Collection;
    case Void;
    case Other;
}
