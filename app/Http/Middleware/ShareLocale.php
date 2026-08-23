<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the interface language (spec §27): cookie first, then browser
 * preference, defaulting to English. Only supported locales pass through.
 * The resolved locale is shared with Inertia and applied server-side.
 */
class ShareLocale
{
    public const SUPPORTED = ['en', 'fr', 'ar'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->cookie('locale');

        if (! in_array($locale, self::SUPPORTED, true)) {
            $preferred = substr((string) $request->getPreferredLanguage(), 0, 2);
            $locale = in_array($preferred, self::SUPPORTED, true) ? $preferred : 'en';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
