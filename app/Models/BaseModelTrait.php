<?php
declare(strict_types=1);

namespace App\Models;

use Closure;

trait BaseModelTrait
{
    public function tap(Closure $callback): static
    {
        $callback($this);

        return $this;
    }
}
