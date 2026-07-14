<?php
declare(strict_types=1);

namespace App\Support\VO;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use LogicException;

class RouteParameterUnresolvableException extends LogicException implements Responsable
{
    public static function make(string $parameter): static
    {
        return new static(
            sprintf('Route parameter "%s" is not resolvable', $parameter)
        );
    }

    public function toResponse($request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}