<?php
declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Post;
use Illuminate\Http\Request;

/**
 * @property Post $resource
 */
class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->whenHas('id'),
            'title' => $this->whenHas('title'),
            'content' => $this->whenHas('content'),
            $this->mergeWhenHasDeletedAt(),
            $this->mergeWhenHasTimestamps(),
        ];
    }
}
