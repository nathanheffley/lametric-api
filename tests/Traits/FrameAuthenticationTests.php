<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\Config;

trait FrameAuthenticationTests
{
    // Basic base64_encode('test:password')
    protected string $basicAuth = 'Basic dGVzdDpwYXNzd29yZA==';

    protected array $noAuthFrame = [
        'text' => 'INVALID AUTH',
        'icon' => 12345,
    ];

    public function test_no_basic_auth_returns_no_auth_frame()
    {
        Config::set('services.lametric.icons.invalid_auth', 12345);

        $response = $this->get($this->path);

        $response->assertStatus(200);
        $response->assertExactJson([
            'frames' => [$this->noAuthFrame],
        ]);
    }

    public function test_wrong_basic_auth_throws_exception()
    {
        Config::set('services.lametric.icons.invalid_auth', 12345);

        $response = $this->get($this->path, [
            'Authorization' => 'Basic incorrect-auth',
        ]);

        $response->assertStatus(200);
        $response->assertExactJson([
            'frames' => [$this->noAuthFrame],
        ]);
    }
}
