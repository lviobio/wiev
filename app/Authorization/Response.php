<?php
declare(strict_types=1);

namespace App\Authorization;

use Illuminate\Auth\Access\Response as BaseResponse;
use InvalidArgumentException;

class Response extends BaseResponse
{
    public function __construct($allowed, $message = '', $code = null)
    {
        if (!$allowed && empty($message)) {
            throw new InvalidArgumentException('Message is required when denied');
        }

        parent::__construct($allowed, $message, $code);
    }

    public static function allow($message = null, $code = null): static
    {
        return new static(true, $message, $code);
    }

    public static function deny($message = null, $code = null): static
    {
        return new static(false, $message, $code);
    }
}
