<?php
declare(strict_types=1);

use App\Core\Upload\Console\Commands\PruneTemporaryUploadsCommand;
use App\Core\Upload\Exceptions\TemporaryUploadAlreadyUsedException;
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
    // `withRouting(commands: ...)` already calls withCommands() with a non-empty array,
    // which suppresses the app/Console/Commands scan. Re-enable it explicitly, plus
    // PruneTemporaryUploadsCommand - it lives outside app/Console/Commands, next to the
    // rest of the Upload module, and a non-empty argument here doesn't fall back to the
    // default path on its own.
    ->withCommands([
        app_path('Console/Commands'),
        PruneTemporaryUploadsCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function (Request $request): bool {
            return Str::startsWith($request->path(), 'api/');
        });

        // Two requests raced for the same staged file and this one lost at commit
        // time (see App\Core\Upload\TemporaryUploadClaimer). Nothing of it was
        // written, so the client can simply pick another file and retry.
        $exceptions->renderable(function (TemporaryUploadAlreadyUsedException $e, Request $request): ?JsonResponse {
            if (!$request->wantsJson()) {
                return null;
            }

            return response()->json(['message' => $e->getMessage()], 409);
        });

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
