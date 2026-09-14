<?php
declare(strict_types=1);

namespace Tests\Feature\Support\OpenApi;

use App\Core\Upload\VO\NewUpload;
use App\Modules\Post\Domain\VO\PostContent;
use App\Modules\Post\Domain\VO\PostCover;
use App\Modules\Post\Domain\VO\PostFileIdentifier;
use App\Modules\Post\Domain\VO\PostFileName;
use App\Modules\Post\Domain\VO\PostIdentifier;
use App\Modules\Post\Domain\VO\PostTitle;
use App\Support\OpenApi\Swagger\ScalarVoType;

it('reads minLength and maxLength off a validated string value\'s own rules', function () {
    // PostTitle::rules() === ['string', 'min:3', 'max:255']
    $type = ScalarVoType::for(PostTitle::class);

    expect($type->type)->toBe('string')
        ->and($type->minLength)->toBe(3)
        ->and($type->maxLength)->toBe(255);
});

it('leaves minLength null when the rules never declare a minimum', function () {
    // PostContent::rules() === ['string', 'max:65535'] - no `min:`.
    $type = ScalarVoType::for(PostContent::class);

    expect($type->minLength)->toBeNull()
        ->and($type->maxLength)->toBe(65535);
});

it('reads the same constraint for every ValidatedStringValue, not just one', function () {
    // PostFileName::rules() === ['string', 'max:255']
    expect(ScalarVoType::for(PostFileName::class)->maxLength)->toBe(255);
});

it('reads a stored file value as a uuid, not binary', function () {
    // PostCover doesn't carry bytes - the client references a staged file by its
    // temporary-upload identifier (see App\Support\Spatie\Data\StoredFileValueCast),
    // so its wire shape is a uuid string, same as PostFileIdentifier's.
    $type = ScalarVoType::for(PostCover::class);

    expect($type->type)->toBe('string')
        ->and($type->format)->toBe('uuid')
        ->and($type->minLength)->toBeNull()
        ->and($type->maxLength)->toBeNull();
});

it('reads an uploaded file value as binary', function () {
    // NewUpload is the one file value that carries actual bytes - the temporary
    // upload endpoint's own request.
    $type = ScalarVoType::for(NewUpload::class);

    expect($type->type)->toBe('string')
        ->and($type->format)->toBe('binary')
        ->and($type->minLength)->toBeNull()
        ->and($type->maxLength)->toBeNull();
});

it('does not read length constraints into an identifier VO either', function () {
    expect(ScalarVoType::for(PostIdentifier::class)->minLength)->toBeNull()
        ->and(ScalarVoType::for(PostFileIdentifier::class)->maxLength)->toBeNull();
});
