<?php
declare(strict_types=1);

namespace App\Support\OpenApi;

use Attribute;
use OpenApi\Attributes as OA;

/**
 * Список ресурсов без пагинации — конверт `{data: [...]}`.
 *
 * Нужен там, где коллекцию отдаёт Action, а не Query: пагинировать нечего,
 * и обещать клиенту пагинированный конверт было бы враньём.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ResourceCollectionResponse extends OA\Response
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
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: $ref),
                    ),
                ],
            ),
        );
    }
}
