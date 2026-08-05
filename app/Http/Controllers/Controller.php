<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

// The spec version is driven by L5_SWAGGER_OPEN_API_SPEC_VERSION (default 3.0.0);
// l5-swagger's generator overrides whatever is declared here, so it is left unset.
// Keep it at 3.0.x while the schemas below use `nullable` (dropped in 3.1+).
#[OA\OpenApi]
#[OA\Info(version: '0.0.1', title: 'Wiev API')]
#[OA\Server(url: 'http://localhost:7100', description: 'API server')]
#[OA\SecurityScheme(securityScheme: 'bearerAuth', type: 'http', description: 'Bearer token issued by Laravel Sanctum', scheme: 'bearer')]
#[OA\Schema(schema: 'PaginationLink', properties: [
    new OA\Property(property: 'url', type: 'string', nullable: true),
    new OA\Property(property: 'label', type: 'string'),
    new OA\Property(property: 'page', type: 'integer', nullable: true),
    new OA\Property(property: 'active', type: 'boolean'),
], type: 'object')]
#[OA\Schema(schema: 'Pagination', properties: [
    new OA\Property(property: 'links', properties: [
        new OA\Property(property: 'first', type: 'string', nullable: true),
        new OA\Property(property: 'last', type: 'string', nullable: true),
        new OA\Property(property: 'prev', type: 'string', nullable: true),
        new OA\Property(property: 'next', type: 'string', nullable: true),
    ], type: 'object'),
    new OA\Property(property: 'meta', properties: [
        new OA\Property(property: 'current_page', type: 'integer'),
        new OA\Property(property: 'from', type: 'integer', nullable: true),
        new OA\Property(property: 'last_page', type: 'integer'),
        new OA\Property(property: 'links', type: 'array', items: new OA\Items(ref: '#/components/schemas/PaginationLink')),
        new OA\Property(property: 'path', type: 'string'),
        new OA\Property(property: 'per_page', type: 'integer'),
        new OA\Property(property: 'to', type: 'integer', nullable: true),
        new OA\Property(property: 'total', type: 'integer'),
    ], type: 'object'),
])]
#[OA\Schema(schema: 'CursorPagination', properties: [
    new OA\Property(property: 'links', properties: [
        new OA\Property(property: 'first', type: 'string', nullable: true),
        new OA\Property(property: 'last', type: 'string', nullable: true),
        new OA\Property(property: 'prev', type: 'string', nullable: true),
        new OA\Property(property: 'next', type: 'string', nullable: true),
    ], type: 'object'),
    new OA\Property(property: 'meta', properties: [
        new OA\Property(property: 'path', type: 'string'),
        new OA\Property(property: 'per_page', type: 'integer'),
        new OA\Property(property: 'next_cursor', type: 'string', nullable: true),
        new OA\Property(property: 'prev_cursor', type: 'string', nullable: true),
    ], type: 'object'),
])]
abstract class Controller
{
    //
}
