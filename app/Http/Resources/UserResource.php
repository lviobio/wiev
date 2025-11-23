<?php
declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->whenHas('id'),
            'email' => $this->whenHas('email'),
        ];
    }
}
