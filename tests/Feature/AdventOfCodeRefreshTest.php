<?php

namespace Tests\Feature;

use App\Http\Controllers\AdventOfCodeController;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Traits\FrameAuthenticationTests;

class AdventOfCodeRefreshTest extends TestCase
{
    // Technically we don't need to return any frames, but it makes
    // sense to reuse the same authentication functionality.
    use FrameAuthenticationTests;

    protected string $path = '/api/advent-of-code/refresh';

    public function test_refresh_forgets_cached_value()
    {
        Cache::shouldReceive('forget')
            ->once()
            ->with(AdventOfCodeController::KEY);

        $response = $this->get($this->path, [
            'Authorization' => $this->basicAuth,
        ]);

        $response->assertStatus(200);
    }
}
