<?php
declare(strict_types=1);

namespace App\Support\Http\Generator;

use OpenApi\Attributes as OA;

enum HttpMethod: string
{
    case Get = 'get';
    case Post = 'post';
    case Put = 'put';
    case Patch = 'patch';
    case Delete = 'delete';

    /**
     * The `OpenApi\Attributes` class documenting this verb.
     *
     * @return class-string
     */
    public function attributeClass(): string
    {
        return match ($this) {
            self::Get => OA\Get::class,
            self::Post => OA\Post::class,
            self::Put => OA\Put::class,
            self::Patch => OA\Patch::class,
            self::Delete => OA\Delete::class,
        };
    }

    /**
     * The `Route::` method registering this verb.
     */
    public function routeMethod(): string
    {
        return $this->value;
    }

    /**
     * Whether PHP refuses to parse a multipart body for this verb, forcing Laravel's
     * `_method` spoofing - and with it a documented verb that differs from the real one.
     */
    public function needsMethodSpoofingForUploads(): bool
    {
        return $this === self::Put || $this === self::Patch;
    }
}
