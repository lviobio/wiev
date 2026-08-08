<?php
declare(strict_types=1);

namespace App\Support\Http\Generator\OpenApi;

use App\Support\Http\Generator\ControllerDefinition;
use App\Support\Http\Generator\EndpointKind;
use App\Support\Http\Generator\EndpointPlan;
use App\Support\Http\Generator\GeneratorException;
use App\Support\Http\Generator\Introspection\ReturnKind;
use App\Support\Http\Generator\Php\ClassRef;
use App\Support\Http\Generator\Php\Expr;
use App\Support\Http\Generator\Php\Literal;
use App\Support\Http\Generator\Php\NewExpr;
use App\Support\OpenApi\PaginatedResourceResponse;
use App\Support\OpenApi\SingleResourceResponse;
use OpenApi\Attributes as OA;

/**
 * Responses of an endpoint, derived from what its Action returns.
 */
final class ResponsesFactory
{
    /**
     * Status documented when a route parameter names a record that may not exist.
     *
     * Not 404: the `ModelNotFoundException` renderer in `bootstrap/app.php` answers
     * missing models with 424, which is what clients actually receive.
     */
    public const string NOT_FOUND_STATUS = '424';

    /**
     * @return list<Expr>
     */
    public function build(ControllerDefinition $definition, EndpointPlan $plan): array
    {
        $responses = [$this->success($definition, $plan)];

        if ($plan->looksUpByRouteParameter) {
            $responses[] = new NewExpr(OA\Response::class, [
                'response' => new Literal(self::NOT_FOUND_STATUS),
                'description' => new Literal($definition->naming()->notFoundDescription()),
            ]);
        }

        return array_values(array_filter($responses));
    }

    private function success(ControllerDefinition $definition, EndpointPlan $plan): Expr
    {
        $kind = $plan->endpoint->kind;
        $description = $definition->naming()->successDescription($kind);

        // Nothing to reflect on for an inline callback: what it returns is arbitrary PHP.
        // A bare 200 keeps the operation valid; describe it with ->addResponses().
        if ($plan->isCallback()) {
            return new NewExpr(OA\Response::class, [
                'response' => new Literal('200'),
                'description' => new Literal($description ?? 'Successful operation'),
            ]);
        }

        if ($plan->returnKind === ReturnKind::Void) {
            return new NewExpr(OA\Response::class, [
                'response' => new Literal('204'),
                'description' => new Literal($description ?? 'Successful operation'),
            ]);
        }

        $resource = $definition->getResourceClass()
            ?? throw GeneratorException::missingResource(
                $definition->controllerClass,
                $plan->endpoint->controllerMethod,
            );

        if ($plan->returnKind === ReturnKind::Collection) {
            $arguments = [new ClassRef($resource)];

            if ($plan->endpoint->isCursorPaginated()) {
                $arguments['paginationType'] = new Literal('CursorPagination');
            }

            return new NewExpr(PaginatedResourceResponse::class, $arguments);
        }

        $arguments = [new ClassRef($resource)];

        if ($kind === EndpointKind::Store) {
            $arguments['response'] = new Literal('201');
        }

        if ($description !== null) {
            $arguments['description'] = new Literal($description);
        }

        return new NewExpr(SingleResourceResponse::class, $arguments);
    }
}
