<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('Accept-Language');
        if (in_array($locale, ['ar', 'en'])) {
            app()->setLocale($locale);
            
            // Resolve user using sanctum guard directly
            $user = $request->user('sanctum');
            if ($user && $user->locale !== $locale) {
                $user->locale = $locale;
                $user->save();
            }
        } else {
            app()->setLocale('ar'); // Default to Arabic
        }

        return $next($request);
    }
}
