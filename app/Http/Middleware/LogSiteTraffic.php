<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LogSiteTraffic
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('LogSiteTraffic middleware reached', [
            'path' => $request->path(),
            'method' => $request->method(),
        ]);

        $response = $next($request);

        try {
            if ($request->isMethod('GET')) {
                DB::connection('oracle')->table('site_traffic')->insert([
                    'visit_date' => now(),
                    'page_name' => $request->path(),
                    'user_id' => Auth::check() ? Auth::user()->user_id : null,
                ]);

                Log::info('Site traffic inserted', [
                    'path' => $request->path(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Site traffic insert failed', [
                'message' => $e->getMessage(),
            ]);
        }

        return $response;
    }
}
