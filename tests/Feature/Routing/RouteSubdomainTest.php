<?php

/*
 * route_subdomain without route_domain keeps the pre-2.6.0 behaviour: the
 * domain is guessed from the booting request's host, RouteSubdomain 404s a
 * host whose first label is not the subdomain, and nothing global is
 * registered. The one change is that a two-label host (the bare domain) is
 * redirected to the subdomain on GET and HEAD instead of 404ing.
 */

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use VentureDrake\LaravelCrm\Http\Middleware\RedirectToRouteDomain;
use VentureDrake\LaravelCrm\Http\Middleware\RouteSubdomain;
use VentureDrake\LaravelCrm\Support\RouteDomain;

trait BootsWithRouteSubdomainOnly
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('laravel-crm.route_domain', null);
        $app['config']->set('laravel-crm.route_subdomain', 'crm');
        $app['config']->set('laravel-crm.route_prefix', '');
    }
}

uses(BootsWithRouteSubdomainOnly::class);

test('the domain is still guessed from the request host', function () {
    expect(RouteDomain::forRoutes(Request::create('http://www.example.com/')))->toBe('crm.example.com')
        ->and(RouteDomain::forRoutes(Request::create('http://www.example.com.au/')))->toBe('crm.example.com.au')
        ->and(RouteDomain::forRoutes(Request::create('http://example.com/')))->toBeNull()
        ->and(RouteDomain::forRoutes(Request::create('http://a.b.c.example.com.au/')))->toBeNull();

    // Booted from http://localhost, so no domain is bound - as before.
    expect(Route::getRoutes()->getByName('laravel-crm.dashboard')->getDomain())->toBeNull();
});

test('RouteSubdomain runs on the CRM and login routes', function () {
    expect(Route::getRoutes()->getByName('laravel-crm.dashboard')->gatherMiddleware())->toContain('crm')
        ->and(app('router')->getMiddlewareGroups()['crm'])->toContain(RouteSubdomain::class)
        ->and(Route::getRoutes()->getByName('laravel-crm.login')->gatherMiddleware())->toContain(RouteSubdomain::class);
});

test('no global redirect is registered without route_domain', function () {
    expect(app(Kernel::class)->hasMiddleware(RedirectToRouteDomain::class))->toBeFalse();
});

test('a two-label host redirects to the subdomain on GET instead of 404ing', function () {
    $this->get('http://urbannurseries.test/dashboard?tab=1')
        ->assertRedirect('http://crm.urbannurseries.test/dashboard?tab=1');

    // The login page too, so the session starts on the subdomain.
    $this->get('http://urbannurseries.test/login')
        ->assertRedirect('http://crm.urbannurseries.test/login');
});

test('HEAD on a two-label host redirects to the subdomain', function () {
    $this->call('HEAD', 'http://urbannurseries.test/dashboard')
        ->assertRedirect('http://crm.urbannurseries.test/dashboard');
});

test('POST on a two-label host still 404s', function () {
    $this->post('http://urbannurseries.test/logout')->assertNotFound();
});

test('a host whose first label is not the subdomain still 404s', function () {
    $this->get('http://www.urbannurseries.com/login')->assertNotFound();
});

test('the subdomain itself is served', function () {
    $this->get('http://crm.urbannurseries.com/login')->assertOk();
});

test('the two-label redirect keeps the port', function () {
    expect(RouteDomain::subdomainRedirectFor(Request::create('http://urbannurseries.test:8000/leads')))
        ->toBe('http://crm.urbannurseries.test:8000/leads');
});
