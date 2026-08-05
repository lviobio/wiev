<?php
declare(strict_types=1);

namespace App\Support\Http\Generator;

enum EndpointKind
{
    case Index;
    case Show;
    case Store;
    case Update;
    case Destroy;
    case Custom;
}
