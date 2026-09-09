<?php
declare(strict_types=1);

namespace App\Modules\Post\Models;

use App\Core\ModelManager\InteractsWithMedia;
use App\Models\BaseModel;
use App\Models\User;
use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\VO\PostCover;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;

#[UseFactory(PostFactory::class)]
class Post extends BaseModel implements HasMedia
{
    use HasFactory;
    use SoftDeletes;
    use InteractsWithMedia;

    protected $fillable = [
        'title',
        'content',
        'published_at',
        'author_user_id',
    ];

    protected $casts = [
        'published_at' => 'immutable_datetime',
    ];

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection(PostMediaCollectionEnum::Cover->value)
            ->singleFile()
            ->registerMediaConversions(function () {
                $this
                    ->addMediaConversion(PostMediaCollectionEnum::CoverConversionThumb->value)
                    ->width(50)
                    ->height(50);
            });
    }

    public function authorUser(): BelongsTo
    {
        return $this->belongsTo(User::class)
            ->withTrashed();
    }
}
