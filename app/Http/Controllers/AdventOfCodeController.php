<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AdventOfCodeController extends Controller
{
    public const KEY = 'advent-of-code-frames';

    public function __invoke(): JsonResponse
    {
        if (Cache::has(self::KEY)) {
            return response()->json(Cache::get(self::KEY));
        }

        $year = now()->year;
        $leaderboard = config('services.advent.leaderboard');
        $response = Http::acceptJson()
            ->withHeaders([
                'Cookie' => 'session='.config('services.advent.cookie'),
            ])
            ->get("https://adventofcode.com/$year/leaderboard/private/view/$leaderboard.json");

        if ($response->failed()) {
            Log::error($response->body());
            return response()->json([
                'frames' => [
                    [
                        'text' => 'ERROR',
                        'icon' => config('services.lametric.icons.error'),
                    ],
                ],
            ]);
        }

        try {
            $data = $response->json('members')[config('services.advent.user')];
        } catch (Throwable $exception) {
            Log::error($exception);
            return response()->json([
                'frames' => [
                    [
                        'text' => 'COULD NOT FIND YOUR DATA',
                        'icon' => config('services.lametric.icons.error'),
                    ],
                ],
            ]);
        }

        $day = now('America/New_York')->day;
        $dayData = $data['completion_day_level'][$day] ?? [];
        $todaysStarCount = count($dayData);

        $starIcon = match ($todaysStarCount) {
            0 => config('services.lametric.icons.empty_star'),
            1 => config('services.lametric.icons.silver_star'),
            2 => config('services.lametric.icons.gold_star'),
            default => config('services.lametric.icons.error'),
        };

        $frames = [
            'frames' => [
                [
                    'text' => "DAY $day",
                    'icon' => $starIcon,
                ],
                [
                    'text' => $data['local_score'] . ' PTS',
                    'icon' => config('services.lametric.icons.gold_trophy'),
                ],
                [
                    'text' => $data['stars'] . ' STRS',
                    'icon' => config('services.lametric.icons.gold_star'),
                ],
            ],
        ];

        Cache::add(self::KEY, $frames, 60 * 60);

        return response()->json($frames);
    }
}
