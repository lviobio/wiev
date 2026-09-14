<?php
declare(strict_types=1);

use App\Core\Upload\Actions\Chunked\AbortChunkedUpload\AbortChunkedUploadAction;
use App\Core\Upload\Actions\Chunked\AppendChunkedUploadChunk\AppendChunkedUploadChunkAction;
use App\Core\Upload\Actions\Chunked\CompleteChunkedUpload\CompleteChunkedUploadAction;
use App\Core\Upload\Actions\Chunked\ShowChunkedUpload\ShowChunkedUploadAction;
use App\Core\Upload\Actions\Chunked\StartChunkedUpload\StartChunkedUploadAction;
use App\Core\Upload\Actions\StoreUpload\StoreUploadAction;
use App\Core\Upload\Http\Controllers\ChunkedUploadController;
use App\Core\Upload\Http\Controllers\UploadController;
use App\Core\Upload\Http\Resources\ChunkedUploadResource;
use App\Core\Upload\Http\Resources\TemporaryUploadResource;
use App\Core\Upload\Models\ChunkedUpload;
use App\Core\Upload\Models\TemporaryUpload;
use App\Support\Data\Filling\FillFromAuthenticatedUser;
use App\Support\Data\Filling\FillFromRouteParameter;
use App\Support\Http\Generator\ControllerDefinition;
use App\Support\Http\Generator\Endpoint;
use App\Support\Http\Generator\HttpMethod;

return [
    ControllerDefinition::make(UploadController::class)
        ->model(TemporaryUpload::class)
        ->resource(TemporaryUploadResource::class)
        ->routePrefix('uploads')
        ->tag('uploads')
        ->endpoints(
            // Any authenticated user may stage a file; ownership (and everything else
            // about who may later claim it) is enforced where it's claimed, not here.
            Endpoint::store(StoreUploadAction::class)
                ->withoutAbility()
                ->fill(
                    new FillFromAuthenticatedUser('actorUser'),
                ),

            // Lives here, not on ChunkedUploadController, because its result is a
            // TemporaryUpload (this controller's resource) - see
            // CompleteChunkedUploadAction's own docblock for why.
            Endpoint::make(
                HttpMethod::Post,
                'chunked/{chunkedUpload}/complete',
                controllerMethod: 'completeChunked',
                routeName: 'chunked.complete',
                actionClass: CompleteChunkedUploadAction::class,
            )
                ->withoutAbility()
                ->fill(
                    new FillFromAuthenticatedUser('actorUser'),
                    new FillFromRouteParameter('id', 'chunkedUpload'),
                ),
        ),

    // A chunked upload's own session resource - separate from UploadController
    // because it has its own resource shape (progress, not a finished upload). See
    // App\Core\Upload\Chunked for the assembly mechanics these endpoints front.
    ControllerDefinition::make(ChunkedUploadController::class)
        ->model(ChunkedUpload::class)
        ->resource(ChunkedUploadResource::class)
        ->routePrefix('uploads/chunked')
        ->routeNamePrefix('uploads.chunked.')
        ->routeParameter('chunkedUpload')
        ->tag('uploads')
        ->endpoints(
            Endpoint::store(StartChunkedUploadAction::class)
                ->withoutAbility()
                ->fill(
                    new FillFromAuthenticatedUser('actorUser'),
                ),

            Endpoint::make(
                HttpMethod::Post,
                Endpoint::MODEL_PARAMETER . '/chunks',
                controllerMethod: 'appendChunk',
                routeName: 'chunks.store',
                actionClass: AppendChunkedUploadChunkAction::class,
            )
                ->withoutAbility()
                ->fill(
                    new FillFromAuthenticatedUser('actorUser'),
                    new FillFromRouteParameter('id', 'chunkedUpload'),
                ),

            Endpoint::show(ShowChunkedUploadAction::class)
                ->withoutAbility()
                ->fill(
                    new FillFromAuthenticatedUser('actorUser'),
                    new FillFromRouteParameter('id', 'chunkedUpload'),
                ),

            Endpoint::destroy(AbortChunkedUploadAction::class)
                ->withoutAbility()
                ->fill(
                    new FillFromAuthenticatedUser('actorUser'),
                    new FillFromRouteParameter('id', 'chunkedUpload'),
                ),
        ),
];
