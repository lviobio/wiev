<?php
declare(strict_types=1);

namespace Tests\Feature\Support\OpenApi;

use App\Enums\AuthAbilityEnum;
use App\Models\User;
use App\Support\OpenApi\Swagger\EnumSchemaType;

/**
 * Nothing in the app has an int-backed or pure enum to test against, so these two are
 * fixtures - {@see AuthAbilityEnum} stands in for the string-backed case, the one shape
 * the app actually uses.
 */
enum EnumSchemaTypeFixtureIntEnum: int
{
    case Low = 1;
    case High = 2;
}

enum EnumSchemaTypeFixturePureEnum
{
    case One;
    case Two;
}

it('reads a string-backed enum as its case values', function () {
    $type = EnumSchemaType::for(AuthAbilityEnum::class);

    expect($type->type)->toBe('string')
        ->and($type->cases)->toBe(['access', 'administer']);
});

it('reads an int-backed enum as its case values', function () {
    $type = EnumSchemaType::for(EnumSchemaTypeFixtureIntEnum::class);

    expect($type->type)->toBe('integer')
        ->and($type->cases)->toBe([1, 2]);
});

it('reads a pure enum by case name, having no other value to offer', function () {
    $type = EnumSchemaType::for(EnumSchemaTypeFixturePureEnum::class);

    expect($type->type)->toBe('string')
        ->and($type->cases)->toBe(['One', 'Two']);
});

it('returns null for a class that is not an enum', function () {
    expect(EnumSchemaType::for(User::class))->toBeNull();
});
