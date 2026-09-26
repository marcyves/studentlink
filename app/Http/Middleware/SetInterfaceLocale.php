<?php

namespace App\Http\Middleware;

use App\Support\InterfaceLocaleResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetInterfaceLocale
{
    public function __construct(private InterfaceLocaleResolver $locales) {}

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->locales->for($request->user());

        App::setLocale($locale);
        Carbon::setLocale($locale);
        View::share('htmlLang', $locale);

        return $next($request);
    }
}
