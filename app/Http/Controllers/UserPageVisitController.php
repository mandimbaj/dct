<?php

namespace App\Http\Controllers;

use App\Support\UserPageVisitRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPageVisitController extends Controller
{
    public function store(Request $request, UserPageVisitRecorder $recorder): JsonResponse
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:2048'],
        ]);

        return response()->json([
            'recorded' => $recorder->recordPath($request, $validated['path']),
        ]);
    }
}
