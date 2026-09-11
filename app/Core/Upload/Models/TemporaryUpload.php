<?php
declare(strict_types=1);

namespace App\Core\Upload\Models;

use App\Models\BaseModel;
use App\Models\User;
use Database\Factories\TemporaryUploadFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A file staged by its owner before being claimed by a real model.
 *
 * Deliberately not `HasMedia` / under `ModelManager`: it never gets a `media` row of
 * its own. Registering media-library conversions against a "pending" collection here
 * would be wasted work at best and wrong at worst - conversions are resolved per
 * (model class, collection) at the moment a Media row is created, so a `Post` cover's
 * `thumb` conversion can only ever be produced by creating that Media row directly on
 * `Post`, which is exactly what claiming an upload does
 * ({@see \App\Support\Spatie\Data\StoredFileValueCast}).
 */
#[UseFactory(TemporaryUploadFactory::class)]
class TemporaryUpload extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'used_at',
    ];

    protected $casts = [
        'used_at' => 'immutable_datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Uploads the given user may turn into media: their own, and either never used
     * or used recently enough to still be kept (`uploads.keep_after_use_minutes`).
     *
     * The one definition of "usable", shared by the validation rule (pre-check),
     * {@see \App\Support\Spatie\Data\StoredFileValueCast} (building the value) and
     * the prune command (the complement: what to delete), so they can't drift apart.
     *
     * @param  Builder<static>  $query
     */
    public function scopeUsableBy(Builder $query, int|string|null $userId): void
    {
        $query->where('user_id', $userId)->where(function (Builder $query): void {
            $query->whereNull('used_at');

            if (static::keptAfterUseFor() !== null) {
                $query->orWhere('used_at', '>=', now()->sub(static::keptAfterUseFor()));
            }
        });
    }

    /**
     * How long a used upload is kept (and stays usable), or null in single-use mode.
     */
    public static function keptAfterUseFor(): ?\DateInterval
    {
        $minutes = (int) config('uploads.keep_after_use_minutes');

        return $minutes > 0 ? new \DateInterval('PT' . $minutes . 'M') : null;
    }
}
