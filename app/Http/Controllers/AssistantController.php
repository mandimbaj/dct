<?php

namespace App\Http\Controllers;

use App\Services\AssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssistantController extends Controller
{
    public function chat(Request $request, AssistantService $assistant): JsonResponse
    {
        $question = $request->string('question')->trim()->toString();
        $history = array_slice((array) $request->input('history', []), -8);
        $country = $request->string('country')->trim()->toString();

        if ($question === '') {
            return response()->json(['error' => 'empty'], 422);
        }

        return response()->json($assistant->answer($question, $history, $country));
    }
}
