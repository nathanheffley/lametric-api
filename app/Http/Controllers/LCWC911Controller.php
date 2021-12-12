<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class LCWC911Controller extends Controller
{
    public const KEY = 'lcwc-911-frames';

    public function __invoke(): JsonResponse
    {
        if (Cache::has(self::KEY)) {
            return response()->json(Cache::get(self::KEY));
        }

        $response = Http::get('https://webcad.lcwc911.us/Pages/Public/LiveIncidentsFeed.aspx');

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

        $incidents = [];

        try {
            $data = simplexml_load_string($response->body())->channel->item;

            foreach ($data as $item) {
                if (str_contains($item->title, 'ROUTINE TRANSFER')) {
                    continue;
                }

                [$area, $intersection, $vehicles] = explode(';', $item->description);

                if (empty($vehicles)) {
                    $vehicles = 'NO VEHICLES';
                } else {
                    $count = count(explode('<br>', $vehicles));
                    $vehicles = $count . ' ' . Str::plural('VEHICLE', $count);
                }

                $incidents[] = [
                    'text' => $item->title . ' - ' . $area . ' - ' . trim($intersection) . ' - ' . $vehicles,
                ];
            }
        } catch (Throwable $exception) {
            Log::error($exception);
            return response()->json([
                'frames' => [
                    [
                        'text' => 'ERROR',
                        'icon' => config('services.lametric.icons.error'),
                    ],
                ],
            ]);
        }

        if (empty($incidents)) {
            $frames = [
                'frames' => [
                    [
                        'text' => 'NO LCWC LIVE INCIDENTS AT THIS TIME',
                    ],
                ],
            ];
        } else {
            $frames = [
                'frames' => [
                    [
                        'text' => 'LCWC LIVE INCIDENTS',
                    ],
                    ...$incidents,
                ],
            ];
        }

        Cache::add(self::KEY, $frames, 5 * 60);

        return response()->json($frames);
    }
}
