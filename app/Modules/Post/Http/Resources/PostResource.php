<?php
declare(strict_types=1);

namespace App\Modules\Post\Http\Resources;

use App\Http\Resources\JsonResource;
use App\Modules\Post\Models\Post;
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
