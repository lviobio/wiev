<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chunked_uploads', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Allocated once at start (App\Core\Upload\Actions\Chunked\StartChunkedUpload\
            // StartChunkedUploadAction), same directory/uuid/original_name convention as
            // StoreUploadAction, then frozen for the session's lifetime - a chunked
            // upload occupies its final resting place from the first byte, whichever
            // strategy is assembling it (see ChunkedUploadStrategyResolver).
            $table->string('disk');
            $table->string('path');

            $table->string('original_name');
            // Client-declared, not sniffed - see App\Core\Upload\Chunked\
            // S3ChunkedUploadStrategy for why that's unavoidable there; the local
            // strategy re-derives it for real once the file is whole.
            $table->string('mime_type')->nullable();

            $table->unsignedBigInteger('total_size');
            $table->unsignedBigInteger('received_bytes')->default(0);

            // Strategy-private bookkeeping - e.g. the S3 strategy's multipart upload id
            // and part ETags. Opaque to everything outside the strategy that wrote it.
            $table->json('provider_state')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);

            // Same invariant, same reason as temporary_uploads' own unique(disk, path).
            $table->unique(['disk', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chunked_uploads');
    }
};
