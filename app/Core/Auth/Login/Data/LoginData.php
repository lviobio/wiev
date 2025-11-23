<?php
declare(strict_types=1);

namespace App\Core\Auth\Login\Data;

use Spatie\LaravelData\Attributes\Validation\Bail;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class LoginData extends Data
{
    #[Required]
    #[Email]
    #[Bail]
    public string $email;

    #[Required]
    #[StringType]
    public string $password;
}
