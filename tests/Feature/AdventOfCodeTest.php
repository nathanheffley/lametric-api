<?php

namespace Tests\Feature;

use App\Http\Controllers\AdventOfCodeController;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Traits\FrameAuthenticationTests;

class AdventOfCodeTest extends TestCase
{
    use FrameAuthenticationTests;

    protected string $path = '/api/advent-of-code';

    protected int $user = 99999;

    protected int $noStarIcon     = 11111;
    protected int $silverStarIcon = 22222;
    protected int $goldStarIcon   = 33333;
    protected int $goldTrophyIcon = 44444;
    protected int $errorIcon      = 55555;

    public function setUp(): void
    {
        parent::setUp();

        Config::set('services.advent.user', $this->user);

        Config::set('services.lametric.icons.empty_star',  $this->noStarIcon);
        Config::set('services.lametric.icons.silver_star', $this->silverStarIcon);
        Config::set('services.lametric.icons.gold_star',   $this->goldStarIcon);
        Config::set('services.lametric.icons.gold_trophy', $this->goldTrophyIcon);
        Config::set('services.lametric.icons.error',       $this->errorIcon);
    }

    public function test_fetch_data_includes_correct_headers()
    {
        Config::set('services.advent.cookie', 'super-secret-cookie-value');
        Config::set('services.advent.leaderboard', 12345);

        Carbon::setTestNow('2021-12-10 12:00:00');

        Http::fake([
            'adventofcode.com/*' => Http::response([], 500),
        ]);

        $this->get($this->path, [
            'Authorization' => $this->basicAuth,
        ]);

        Http::assertSent(function (Request $request) {
            return $request->hasHeaders([
                'Accept' => 'application/json',
                'Cookie' => 'session=super-secret-cookie-value',
            ]) && $request->url() === 'https://adventofcode.com/2021/leaderboard/private/view/12345.json';
        });
    }

    public function test_failed_data_fetch_returns_error_frame()
    {
        Carbon::setTestNow('2021-12-10 12:00:00');

        Http::fake([
            'adventofcode.com/*' => Http::response([], 400),
        ]);

        $response = $this->get($this->path, [
            'Authorization' => $this->basicAuth,
        ]);

        $response->assertStatus(200);
        $response->assertExactJson([
            'frames' => [
                [
                    'text' => 'ERROR',
                    'icon' => $this->errorIcon,
                ],
            ],
        ]);
    }

    public function test_unknown_user_returns_error_frame()
    {
        Carbon::setTestNow('2021-12-10 12:00:00');

        Http::fake([
            'adventofcode.com/*' => Http::response([
                'members' => [
                    11111 => [
                        'stars' => 2,
                        'local_score' => 10,
                        'completion_day_level' => [
                            1 => [
                                1 => ['get_star_ts' => 1638335029],
                                2 => ['get_star_ts' => 1638335388]
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->get($this->path, [
            'Authorization' => $this->basicAuth,
        ]);

        $response->assertStatus(200);
        $response->assertExactJson([
            'frames' => [
                [
                    'text' => 'COULD NOT FIND YOUR DATA',
                    'icon' => $this->errorIcon,
                ],
            ],
        ]);
    }

    public function test_user_with_no_completion_data()
    {
        Carbon::setTestNow('2021-12-10 12:00:00');

        $expectedFrames = [
            'frames' => [
                [
                    'text' => 'DAY 10',
                    'icon' => $this->noStarIcon,
                ],
                [
                    'text' => '0 PTS',
                    'icon' => $this->goldTrophyIcon,
                ],
                [
                    'text' => '0 STRS',
                    'icon' => $this->goldStarIcon,
                ],
            ],
        ];

        Cache::shouldReceive('has')
            ->once()
            ->with(AdventOfCodeController::KEY)
            ->andReturnFalse();

        Cache::shouldReceive('add')
            ->once()
            ->with(AdventOfCodeController::KEY, $expectedFrames, 60 * 60);

        Http::fake([
            'adventofcode.com/*' => Http::response([
                'members' => [
                    11111 => [
                        'stars' => 2,
                        'local_score' => 10,
                        'completion_day_level' => [
                            10 => [
                                1 => ['get_star_ts' => 1638335029],
                                2 => ['get_star_ts' => 1638335388]
                            ],
                        ],
                    ],
                    $this->user => [
                        'stars' => 0,
                        'local_score' => 0,
                        'completion_day_level' => [],
                    ],
                ],
            ]),
        ]);

        $response = $this->get($this->path, [
            'Authorization' => $this->basicAuth,
        ]);

        $response->assertStatus(200);
        $response->assertExactJson($expectedFrames);
    }

    public function test_user_with_only_previous_days_completion_data()
    {
        Carbon::setTestNow('2021-12-10 12:00:00');

        $expectedFrames = [
            'frames' => [
                [
                    'text' => 'DAY 10',
                    'icon' => $this->noStarIcon,
                ],
                [
                    'text' => '12 PTS',
                    'icon' => $this->goldTrophyIcon,
                ],
                [
                    'text' => '3 STRS',
                    'icon' => $this->goldStarIcon,
                ],
            ],
        ];

        Cache::shouldReceive('has')
            ->once()
            ->with(AdventOfCodeController::KEY)
            ->andReturnFalse();

        Cache::shouldReceive('add')
            ->once()
            ->with(AdventOfCodeController::KEY, $expectedFrames, 60 * 60);

        Http::fake([
            'adventofcode.com/*' => Http::response([
                'members' => [
                    $this->user => [
                        'stars' => 3,
                        'local_score' => 12,
                        'completion_day_level' => [
                            4 => [
                                1 => ['get_star_ts' => 10000001],
                                2 => ['get_star_ts' => 10000002],
                            ],
                            9 => [
                                1 => ['get_star_ts' => 10000003],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->get($this->path, [
            'Authorization' => $this->basicAuth,
        ]);

        $response->assertStatus(200);
        $response->assertExactJson($expectedFrames);
    }

    public function test_user_with_one_of_todays_stars()
    {
        Carbon::setTestNow('2021-12-10 12:00:00');

        $expectedFrames = [
            'frames' => [
                [
                    'text' => 'DAY 10',
                    'icon' => $this->silverStarIcon,
                ],
                [
                    'text' => '15 PTS',
                    'icon' => $this->goldTrophyIcon,
                ],
                [
                    'text' => '4 STRS',
                    'icon' => $this->goldStarIcon,
                ],
            ],
        ];

        Cache::shouldReceive('has')
            ->once()
            ->with(AdventOfCodeController::KEY)
            ->andReturnFalse();

        Cache::shouldReceive('add')
            ->once()
            ->with(AdventOfCodeController::KEY, $expectedFrames, 60 * 60);

        Http::fake([
            'adventofcode.com/*' => Http::response([
                'members' => [
                    $this->user => [
                        'stars' => 4,
                        'local_score' => 15,
                        'completion_day_level' => [
                            4 => [
                                1 => ['get_star_ts' => 10000001],
                                2 => ['get_star_ts' => 10000002],
                            ],
                            9 => [
                                1 => ['get_star_ts' => 10000003],
                            ],
                            10 => [
                                1 => ['get_star_ts' => 10000004],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->get($this->path, [
            'Authorization' => $this->basicAuth,
        ]);

        $response->assertStatus(200);
        $response->assertExactJson($expectedFrames);
    }

    public function test_user_with_both_of_todays_stars()
    {
        Carbon::setTestNow('2021-12-10 12:00:00');

        $expectedFrames = [
            'frames' => [
                [
                    'text' => 'DAY 10',
                    'icon' => $this->goldStarIcon,
                ],
                [
                    'text' => '20 PTS',
                    'icon' => $this->goldTrophyIcon,
                ],
                [
                    'text' => '5 STRS',
                    'icon' => $this->goldStarIcon,
                ],
            ],
        ];

        Cache::shouldReceive('has')
            ->once()
            ->with(AdventOfCodeController::KEY)
            ->andReturnFalse();

        Cache::shouldReceive('add')
            ->once()
            ->with(AdventOfCodeController::KEY, $expectedFrames, 60 * 60);

        Http::fake([
            'adventofcode.com/*' => Http::response([
                'members' => [
                    $this->user => [
                        'stars' => 5,
                        'local_score' => 20,
                        'completion_day_level' => [
                            4 => [
                                1 => ['get_star_ts' => 10000001],
                                2 => ['get_star_ts' => 10000002],
                            ],
                            9 => [
                                1 => ['get_star_ts' => 10000003],
                            ],
                            10 => [
                                1 => ['get_star_ts' => 10000004],
                                2 => ['get_star_ts' => 10000005],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->get($this->path, [
            'Authorization' => $this->basicAuth,
        ]);

        $response->assertStatus(200);
        $response->assertExactJson($expectedFrames);
    }

    public function test_day_uses_eastern_timezone()
    {
        Carbon::setTestNow('2021-12-11 03:59:59');

        Cache::shouldReceive('has')
            ->once()
            ->with(AdventOfCodeController::KEY)
            ->andReturnFalse();

        Cache::shouldReceive('add')->once();

        Http::fake([
            'adventofcode.com/*' => Http::response([
                'members' => [
                    $this->user => [
                        'stars' => 2,
                        'local_score' => 6,
                        'completion_day_level' => [
                            10 => [
                                1 => ['get_star_ts' => 10000004],
                                2 => ['get_star_ts' => 10000005],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->get($this->path, [
            'Authorization' => $this->basicAuth,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'frames' => [
                [
                    'text' => 'DAY 10',
                    'icon' => $this->goldStarIcon,
                ],
            ],
        ]);
    }

    public function test_cached_frames_are_returned()
    {
        Carbon::setTestNow('2021-12-10 12:00:00');

        Cache::shouldReceive('has')
            ->once()
            ->with(AdventOfCodeController::KEY)
            ->andReturnTrue();

        Cache::shouldReceive('get')
            ->once()
            ->with(AdventOfCodeController::KEY)
            ->andReturn([
                'frames' => [
                    [
                        'text' => 'CACHED FRAME',
                        'icon' => $this->goldStarIcon,
                    ],
                ],
            ]);

        Http::shouldReceive('get')->never();

        $response = $this->get($this->path, [
            'Authorization' => $this->basicAuth,
        ]);

        $response->assertStatus(200);
        $response->assertExactJson([
            'frames' => [
                [
                    'text' => 'CACHED FRAME',
                    'icon' => $this->goldStarIcon,
                ],
            ],
        ]);
    }
}
