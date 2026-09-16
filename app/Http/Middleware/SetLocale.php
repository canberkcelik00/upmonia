<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const SUPPORTED = ['tr', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = Auth::check()
            ? Auth::user()->locale
            : $request->session()->get('locale');

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = 'tr';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
