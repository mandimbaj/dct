<?php

namespace App\Http\Middleware;

use App\Support\UserPageVisitRecorder;
use Closure;
use Illuminate\Http\Request;

class RecordUserPageVisit
{
    public function __construct(private readonly UserPageVisitRecorder $recorder) {}

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $this->recorder->recordRequest($request, (int) $response->getStatusCode());

        return $response;
    }
}
