<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class Localization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $localization = $request->header('Lang')!=null?$request->header('Lang'):session()->get('Lang');


        $localization = in_array($localization, config('app.available_locales'), true) ? $localization : config('app.locale');
        app()->setLocale($localization);
        Session::put('Lang', $localization);


        return $next($request);
    }
}
