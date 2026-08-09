<?php
declare(strict_types=1);

namespace App\Support\Routing;

use App\Authorization\CheckAuthAbility;
use App\Support\Data\Filling\DataParameterFiller;
use App\Support\Data\Filling\DataPropertyFiller;
use Illuminate\Http\Request;
use Illuminate\Routing\ControllerDispatcher;
use Illuminate\Routing\Route;
use ReflectionMethod;
use ReflectionParameter;

/**
 * Controller dispatcher wiring this application's two controller-level attributes.
 *
 * - {@see DataPropertyFiller} attributes on a parameter say how a Spatie Data object is
 *   populated, so the Data object itself stays free of HTTP concerns.
 * - {@see CheckAuthAbility} on the method says which ability the request needs.
 *
 * Both are declarations; the work lives in {@see DataParameterFiller} and
 * {@see ControllerAbilityAuthorizer}, and this class only decides when to call them.
 */
class AppControllerDispatcher extends ControllerDispatcher
{
    /**
     * @param  \Illuminate\Routing\Route  $route
     * @param  mixed  $controller
     * @param  string  $method
     * @return mixed
     */
    public function dispatch(Route $route, $controller, $method)
    {
        $this->authorize($controller, $method);

        return parent::dispatch($route, $controller, $method);
    }

    /**
     * Runs before the parent resolves parameters: a forbidden request should not get as
     * far as hydrating and validating a Data object.
     */
    protected function authorize(mixed $controller, string $method): void
    {
        if (!method_exists($controller, $method)) {
            return;
        }

        $this->container->make(ControllerAbilityAuthorizer::class)->authorize(
            new ReflectionMethod($controller, $method),
            $this->request(),
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    protected function transformDependency(ReflectionParameter $parameter, $parameters, $skippableValue)
    {
        $filler = $this->container->make(DataParameterFiller::class);

        if ($filler->handles($parameter)) {
            return $filler->fill($parameter, $this->request());
        }

        return parent::transformDependency($parameter, $parameters, $skippableValue);
    }

    private function request(): Request
    {
        /** @var Request */
        return $this->container->make('request');
    }
}
