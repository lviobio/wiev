<?php
declare(strict_types=1);

namespace Tests\Feature\Support\Spatie\Data;

use App\Support\VO\ValidatedStringValue;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final readonly class Nickname extends ValidatedStringValue
{
    public static function rules(): array
    {
        return ['string', 'min:2', 'max:32'];
    }
}

class ProfileData extends Data
{
    public function __construct(
        public Nickname            $nickname,
        public ?Nickname           $alias,
        public Nickname|Optional   $pseudonym,
        #[Rule('string', 'max:5')]
        public Nickname            $shortName,
    )
    {
    }
}

test('rules are inferred from the type of the property', function () {
    $rules = ProfileData::getValidationRules([]);

    expect($rules['nickname'])->toBe(['required', 'string', 'min:2', 'max:32']);
});

test('a nullable value stays optional at the field level', function () {
    $rules = ProfileData::getValidationRules([]);

    expect($rules['alias'])->toBe(['nullable', 'string', 'min:2', 'max:32'])
        ->and($rules['pseudonym'])->toContain('sometimes');
});

test('explicit rules of a field add to the rules of its value', function () {
    $rules = ProfileData::getValidationRules([]);

    // ослабить форму значения атрибутом нельзя — применяется и то, и другое
    expect($rules['shortName'])->toContain('max:5')
        ->and($rules['shortName'])->toContain('min:2')
        ->and($rules['shortName'])->toContain('max:32');
});
