<?php

namespace Tests\Feature;

use App\Http\Middleware\Localization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class LocalizationMiddlewareTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_setLocaleAsArabicLang(): void
    {
        // Given
        $request = Request::create(route("users.list"));
        $request->headers->set("Lang", 'ar');

        $next = function () {
            return response(app()->getLocale());
        };

        // When
        $middleware = new Localization();
        $response = $middleware->handle($request, $next);

        // Then
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals("ar", $response->getContent());
    }


    public function test_setLocaleAsEnglishLang(): void
    {
        // Given
        $request = Request::create(route("users.list"));
        $request->headers->set("Lang", 'en');

        $next = function () {
            return response(app()->getLocale());
        };

        // When
        $middleware = new Localization();
        $response = $middleware->handle($request, $next);

        // Then
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals("en", $response->getContent());
    }


    public function test_setLocaleAsUnSupportedLang(): void
    {
        // Given
        $request = Request::create(route("users.list"));
        $request->headers->set("Lang", 'fr');
        Config::set('app.locale', "en");//set default locale for app if header is not in  avaliable language st local as it
        Config::set('app.available_locales', ["en","ar"]);

        $next = function () {
            return response(app()->getLocale());
        };

        // When
        $middleware = new Localization();
        $response = $middleware->handle($request, $next);

        // Then
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals("en", $response->getContent());
    }
}
