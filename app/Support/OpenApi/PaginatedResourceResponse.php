<?php
declare(strict_types=1);

namespace App\Support\OpenApi;

use Attribute;
use OpenApi\Attributes as OA;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class PaginatedResourceResponse extends OA\Response
{
    /**
     * @param  class-string  $ref
     */
    public function __construct(
        string $ref,
        string $paginationType = 'Pagination',
        string $response = '200',
        string $description = 'Successful operation',
    ) {
        parent::__construct(
            response: $response,
            description: $description,
            content: new OA\JsonContent(
                allOf: [
                    new OA\Schema(
                        properties: [
                            new OA\Property(
                                property: 'data',
                                type: 'array',
                                items: new OA\Items(ref: $ref),
                            ),
                        ],
                    ),
                    new OA\Schema(ref: "#/components/schemas/{$paginationType}"),
                ],
            ),
        );
    }
}
