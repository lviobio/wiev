<?php
declare(strict_types=1);

namespace App\Core\Upload\Models;

use App\Models\BaseModel;
use App\Models\User;
use Database\Factories\ChunkedUploadFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A file being staged in pieces, before it becomes a {@see TemporaryUpload}.
 *
 * A session, not a file in its own right: {@see \App\Core\Upload\Chunked\
 * ChunkedUploadStrategyResolver} decides how bytes actually land on `disk`/`path`
 * (their final resting place, allocated up front - see the migration), and
 * `App\Core\Upload\Actions\Chunked\CompleteChunkedUpload\CompleteChunkedUploadAction`
 * deletes this row the moment it succeeds. Not `HasMedia`, not under `ModelManager` -
 * same rationale as `TemporaryUpload`: a standalone row, never part of an aggregate's
 * transactional graph.
 */
#[UseFactory(ChunkedUploadFactory::class)]
class ChunkedUpload extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'total_size',
        'received_bytes',
        'provider_state',
    ];

    protected $casts = [
        'total_size' => 'integer',
        'received_bytes' => 'integer',
        'provider_state' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isComplete(): bool
    {
        return $this->received_bytes >= $this->total_size;
    }

    /**
     * Sessions the given user may append to / complete / abort. Unlike
     * `TemporaryUpload::scopeUsableBy()` there is no "used but still kept" state here -
     * a session simply stops existing once it completes.
     *
     * @param  Builder<static>  $query
     */
    public function scopeOwnedBy(Builder $query, int|string|null $userId): void
    {
        $query->where('user_id', $userId);
    }
}
