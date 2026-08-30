<?php
declare(strict_types=1);

namespace Tests\Feature\Support\VO;

use App\Modules\Post\Actions\CreatePost\CreatePostData;
use App\Modules\Post\Domain\VO\PostContent;
use App\Modules\Post\Domain\VO\PostTitle;
use App\Support\VO\InvalidValueException;

test('a value that satisfies its rules is accepted', function () {
    expect(new PostTitle('Long enough')->value)->toBe('Long enough');
});

test('a value that breaks its rules is rejected with the validator message', function () {
    expect(fn() => new PostTitle('ab'))
        ->toThrow(InvalidValueException::class, 'PostTitle: The post title field must be at least 3 characters.');
});

test('an empty content is rejected', function () {
    expect(fn() => new PostContent(''))->toThrow(InvalidValueException::class);
});

test('the rules of a value are the rules of the request field', function () {
    $rules = CreatePostData::getValidationRules([]);

    // поле добавляет только обязательность, форму значения описывает VO
    expect($rules['title'])->toBe(['required', ...PostTitle::rules()])
        ->and($rules['content'])->toBe(['nullable', ...PostContent::rules()]);
});
