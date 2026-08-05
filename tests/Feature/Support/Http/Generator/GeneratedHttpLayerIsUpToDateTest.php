<?php
declare(strict_types=1);

namespace Tests\Feature\Support\Http\Generator;

/**
 * The whole point of committing generated files is that they can drift. This is the
 * guard: it fails when someone edits a generated controller by hand, or changes an
 * Action, Data or Query without regenerating.
 */
it('has no stale generated HTTP layer', function () {
    $this->artisan('http:generate --check')->assertSuccessful();
});
