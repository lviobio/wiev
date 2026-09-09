<?php
declare(strict_types=1);

namespace App\Core\ModelManager\Guards;

use App\Core\ModelManager\InteractsWithMedia;
use LogicException;
use Spatie\MediaLibrary\InteractsWithMedia as MediaLibraryInteractsWithMedia;

class MissingMediaIntegrationException extends LogicException
{
    /**
     * @param class-string $modelClass
     */
    public static function make(string $modelClass): self
    {
        return new self(sprintf(
            '%s uses %s directly, so its media would be written outside the transaction '
            . 'of flush() and could not be rolled back. Replace that trait with %s.',
            $modelClass,
            MediaLibraryInteractsWithMedia::class,
            InteractsWithMedia::class,
        ));
    }
}
