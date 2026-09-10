<?php
declare(strict_types=1);

namespace Tests\Feature\Support\OpenApi;

use App\Modules\Post\Domain\VO\PostContent;
use App\Modules\Post\Domain\VO\PostFileName;
use App\Modules\Post\Domain\VO\PostTitle;
use App\Modules\Post\VO\PostCover;
use App\Modules\Post\VO\PostFileIdentifier;
use App\Modules\Post\VO\PostIdentifier;
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

it('does not read length constraints into a FileValue', function () {
    // FileValue matches its own branch in ScalarVoType::for() - the length-constraint
    // reading only ever runs for a plain StringValue, so a file's rules (e.g. an upload
    // size limit) never get misread as a string length.
    $type = ScalarVoType::for(PostCover::class);

    expect($type->type)->toBe('string')
        ->and($type->format)->toBe('binary')
        ->and($type->minLength)->toBeNull()
        ->and($type->maxLength)->toBeNull();
});

it('does not read length constraints into an identifier VO either', function () {
    expect(ScalarVoType::for(PostIdentifier::class)->minLength)->toBeNull()
        ->and(ScalarVoType::for(PostFileIdentifier::class)->maxLength)->toBeNull();
});
