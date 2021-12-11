<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GitHubPRCountController extends Controller
{
    public function __invoke(Request $request)
    {
        return response()->json([
            'frames' => [
                'text' => 'AUTHED',
            ],
        ]);
    }
}
