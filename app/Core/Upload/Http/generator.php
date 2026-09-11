<?php
declare(strict_types=1);

use App\Core\Upload\Actions\StoreUpload\StoreUploadAction;
use App\Core\Upload\Http\Controllers\UploadController;
use App\Core\Upload\Http\Resources\TemporaryUploadResource;
use App\Core\Upload\Models\TemporaryUpload;
use App\Support\Data\Filling\FillFromAuthenticatedUser;
use App\Support\Http\Generator\ControllerDefinition;
use App\Support\Http\Generator\Endpoint;

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
        ),
];
