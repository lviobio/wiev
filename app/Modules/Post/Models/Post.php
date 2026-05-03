<?php
declare(strict_types=1);

namespace App\Modules\Post\Models;

use App\Models\BaseModel;
use App\Models\User;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[UseFactory(PostFactory::class)]
class Post extends BaseModel implements HasMedia
{
    use HasFactory;
    use SoftDeletes;
    use InteractsWithMedia;

    public const string
        MEDIA_COLLECTION_COVER = 'cover',
        MEDIA_COLLECTION_COVER_CONVERSION_THUMB = 'thumb';

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
            ->addMediaCollection(self::MEDIA_COLLECTION_COVER)
            ->singleFile()
            ->registerMediaConversions(function () {
                $this
                    ->addMediaConversion(self::MEDIA_COLLECTION_COVER_CONVERSION_THUMB)
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
