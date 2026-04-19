<?php

declare(strict_types = 1);

namespace Tests\Unit\Support;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTime;
use DateTimeImmutable;
use Spatie\LaravelData\Data;

class TestCarbonImmutableData extends Data
{
    public function __construct(
        public ?CarbonImmutable $date = null,
    ) {}
}

class TestCarbonData extends Data
{
    public function __construct(
        public ?Carbon $date = null,
    ) {}
}

class TestDateTimeData extends Data
{
    public function __construct(
        public ?DateTime $date = null,
    ) {}
}

class TestDateTimeImmutableData extends Data
{
    public function __construct(
        public ?DateTimeImmutable $date = null,
    ) {}
}

// CarbonImmutable

it('casts a timestamp in milliseconds to CarbonImmutable', function () {
    $timestampMs = 1743364500000;

    $data = TestCarbonImmutableData::from(['date' => $timestampMs]);

    expect($data->date)
        ->toBeInstanceOf(CarbonImmutable::class)
        ->and($data->date->getTimestampMs())->toBe($timestampMs);
});

it('casts a date string to CarbonImmutable', function () {
    $data = TestCarbonImmutableData::from(['date' => '2025-03-30T18:00:00+00:00']);

    expect($data->date)
        ->toBeInstanceOf(CarbonImmutable::class)
        ->and($data->date->toDateString())->toBe('2025-03-30');
});

it('handles null value for CarbonImmutable date', function () {
    $data = TestCarbonImmutableData::from(['date' => null]);

    expect($data->date)->toBeNull();
});

it('handles missing CarbonImmutable date field', function () {
    $data = TestCarbonImmutableData::from([]);

    expect($data->date)->toBeNull();
});

it('casts a string integer timestamp to CarbonImmutable', function () {
    $timestampMs = '1743364500000';

    $data = TestCarbonImmutableData::from(['date' => $timestampMs]);

    expect($data->date)
        ->toBeInstanceOf(CarbonImmutable::class)
        ->and($data->date->getTimestampMs())->toBe(1743364500000);
});

// Carbon

it('casts a timestamp in milliseconds to Carbon', function () {
    $timestampMs = 1743364500000;

    $data = TestCarbonData::from(['date' => $timestampMs]);

    expect($data->date)
        ->toBeInstanceOf(Carbon::class)
        ->and($data->date->getTimestampMs())->toBe($timestampMs);
});

it('casts a date string to Carbon', function () {
    $data = TestCarbonData::from(['date' => '2025-03-30T18:00:00+00:00']);

    expect($data->date)
        ->toBeInstanceOf(Carbon::class)
        ->and($data->date->toDateString())->toBe('2025-03-30');
});

// DateTime

it('casts a timestamp in milliseconds to DateTime', function () {
    $timestampMs = 1743364500123;

    $data = TestDateTimeData::from(['date' => $timestampMs]);

    expect($data->date)
        ->toBeInstanceOf(DateTime::class)
        ->and($data->date->format('Uv'))->toBe('1743364500123');
});

it('casts a date string to DateTime', function () {
    $data = TestDateTimeData::from(['date' => '2025-03-30T18:00:00+00:00']);

    expect($data->date)
        ->toBeInstanceOf(DateTime::class)
        ->and($data->date->format('Y-m-d'))->toBe('2025-03-30');
});

// DateTimeImmutable

it('casts a timestamp in milliseconds to DateTimeImmutable', function () {
    $timestampMs = 1743364500456;

    $data = TestDateTimeImmutableData::from(['date' => $timestampMs]);

    expect($data->date)
        ->toBeInstanceOf(DateTimeImmutable::class)
        ->and($data->date->format('Uv'))->toBe('1743364500456');
});

it('casts a date string to DateTimeImmutable', function () {
    $data = TestDateTimeImmutableData::from(['date' => '2025-03-30T18:00:00+00:00']);

    expect($data->date)
        ->toBeInstanceOf(DateTimeImmutable::class)
        ->and($data->date->format('Y-m-d'))->toBe('2025-03-30');
});
