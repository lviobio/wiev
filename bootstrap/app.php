<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (NotFoundHttpException $e, Request $request): ?JsonResponse {
            $previous = $e->getPrevious();
            if ($previous instanceof ModelNotFoundException) {
                if ($request->wantsJson()) {
                    $modelClass = $previous->getModel();
                    /** @var Model $model */
                    $model = $modelClass::getModel();
                    $message = __('Resource not found: ') . $model->getMorphClass();
                    if (!empty($previous->getIds())) {
                        $message .= ' (' . implode(', ', $previous->getIds()) . ')';
                    }
                    return response()->json(
                        ['message' => $message],
                        424 // (Model not found, custom code for this error)
                    );
                }
            }

            return null;
        });
    })->create();
