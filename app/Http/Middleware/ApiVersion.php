<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiVersion
{
    /**
     * Add API version headers to every response.
     *
     * Accepts requests with:
     *   - Accept: application/json  (default)
     *   - Accept: application/vnd.dental.v1+json  (versioned)
     *
     * Always adds X-API-Version header to the response.
     */
    public function handle(Request $request, Closure $next, string $version = 'v1'): Response
    {
        $accept = $request->header('Accept', '');

        // If a versioned Accept header is sent, validate it matches this route group
        if (preg_match('#application/vnd\.dental\.(v\d+)\+json#', $accept, $m)) {
            if ($m[1] !== $version) {
                return response()->json([
                    'success' => false,
                    'message' => "API version mismatch. This endpoint serves {$version}, but {$m[1]} was requested.",
                ], 406);
            }
        }

        $response = $next($request);

        // Inject version headers into every API response
        $response->headers->set('X-API-Version', $version);
        $response->headers->set('X-API-Deprecation', 'false');

        return $response;
    }
}
