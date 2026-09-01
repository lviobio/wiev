<?php
declare(strict_types=1);

namespace App\Modules\Post\Models;

use App\Models\BaseModel;
use App\Models\User;
use App\Modules\Post\Enums\PostMediaCollectionEnum;
use App\Modules\Post\VO\PostCover;
use App\Support\Spatie\MediaLibrary\DeferredMedia;
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

    /**
     * Обложка. null — снять текущую.
     *
     * И то и другое произойдёт после коммита flush(): media library пишет
     * файлы вне транзакции, откатить их нельзя.
     */
    public function setCover(?PostCover $cover): void
    {
        $media = app(DeferredMedia::class);

        if ($cover === null) {
            $media->clear($this, PostMediaCollectionEnum::Cover->value);

            return;
        }

        $media->add($this, $cover)->toMediaCollection(PostMediaCollectionEnum::Cover->value);
    }

    public function authorUser(): BelongsTo
    {
        return $this->belongsTo(User::class)
            ->withTrashed();
    }
}
