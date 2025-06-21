<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiLog;
use Illuminate\Support\Facades\Auth;

class LogApiUsage
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Log only for API routes
        if ($request->is('api/*')) {
            $user = Auth::user();
            ApiLog::create([
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? $request->input('email') ?? '-',
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'ip_address' => $request->ip(),
            ]);
        }
        return $response;
    }
}
