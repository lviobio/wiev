<?php
declare(strict_types=1);

namespace App\Support\OpenApi;

use Attribute;
use OpenApi\Attributes as OA;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class SingleResourceResponse extends OA\Response
{
    /**
     * @param  class-string  $ref
     */
    public function __construct(
        string $ref,
        string $response = '200',
        string $description = 'Successful operation',
    ) {
        parent::__construct(
            response: $response,
            description: $description,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'data', ref: $ref),
                ],
            ),
        );
    }
}
