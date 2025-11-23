<?php
declare(strict_types=1);

return [
    App\Providers\AppServiceProvider::class,
    App\Core\Auth\Providers\CoreAuthServiceProvider::class,
    App\Modules\Post\Providers\PostModuleServiceProvider::class,
];
