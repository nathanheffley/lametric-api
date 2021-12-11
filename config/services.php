<?php

return [

    'lametric' => [
        'icons' => [
            'invalid_auth' => env('LAMETRIC_ICONS_INVALID_AUTH', 47770),
            'error' => env('LAMETRIC_ICONS_ERROR', 47755),

            'empty_star' => env('LAMETRIC_ICONS_EMPTY_STAR', 47753),
            'silver_star' => env('LAMETRIC_ICONS_SILVER_STAR', 47749),
            'gold_star' => env('LAMETRIC_ICONS_GOLD_STAR', 47748),
            'gold_trophy' => env('LAMETRIC_ICONS_GOLD_TROPHY', 47759),
        ],
    ],

    'advent' => [
        'user' => env('ADVENT_OF_CODE_USER'),
        'cookie' => env('ADVENT_OF_CODE_COOKIE'),
        'leaderboard' => env('ADVENT_OF_CODE_LEADERBOARD'),
    ],

];
