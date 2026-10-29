<?php

/*
 * config('laravel-crm.route_domain') binds the main, auth and API route groups
 * to one fixed host, whatever host the app was booted from, and redirects the
 * bare domain and www. there. Routes are registered at boot, so the config is
 * applied in getEnvironmentSetUp, before the provider boots.
 *
 * route_subdomain is set too, to show route_domain wins over it.
 */

use Illuminate\Http\Request;
use Illuminate\Routing\CompiledRouteCollection;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use VentureDrake\LaravelCrm\Http\Middleware\RouteSubdomain;
use VentureDrake\LaravelCrm\Support\RouteDomain;

trait BootsWithRouteDomain
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('laravel-crm.route_domain', 'crm.example.com.au');
        $app['config']->set('laravel-crm.route_subdomain', 'crm');
        $app['config']->set('laravel-crm.route_prefix', '');
    }
}

uses(BootsWithRouteDomain::class);

test('the main, auth and API routes carry route_domain', function () {
    foreach (['laravel-crm.dashboard', 'laravel-crm.leads.index', 'laravel-crm.login', 'laravel-crm.api.v2.auth.me'] as $name) {
        expect(Route::getRoutes()->getByName($name)?->getDomain())->toBe('crm.example.com.au', $name);
    }
});

test('the routes were registered without reading the booting host', function () {
    // Testbench boots from a console request for http://localhost, which the
    // old host guess could not turn into any domain.
    expect(request()->getHost())->toBe('localhost')
        ->and(Route::getRoutes()->getByName('laravel-crm.dashboard')->getDomain())->toBe('crm.example.com.au');
});

test('the deliberately domain-less public groups stay unbound', function () {
    $public = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'p/'));

    expect($public)->not->toBeEmpty()
        ->and($public->map->getDomain()->filter()->all())->toBe([]);
});

test('generated CRM URLs use route_domain', function () {
    expect(route('laravel-crm.dashboard'))->toBe('http://crm.example.com.au/dashboard')
        ->and(route('laravel-crm.login'))->toBe('http://crm.example.com.au/login');
});

test('a compiled (route:cache) collection keeps route_domain and matches the same hosts', function () {
    $routes = Route::getRoutes();
    $routes->refreshNameLookups();
    $routes->refreshActionLookups();

    foreach ($routes as $route) {
        $route->prepareForSerialization();
    }

    // What route:cache writes to bootstrap/cache/routes-v7.php, read back the
    // way the router loads it.
    $compiled = unserialize(serialize($routes->compile()));
    $cached = (new CompiledRouteCollection($compiled['compiled'], $compiled['attributes']))
        ->setRouter(app('router'))
        ->setContainer(app());

    expect($cached->getByName('laravel-crm.dashboard')->getDomain())->toBe('crm.example.com.au')
        ->and($cached->getByName('laravel-crm.api.v2.auth.me')->getDomain())->toBe('crm.example.com.au')
        ->and($cached->match(Request::create('http://crm.example.com.au/dashboard'))->getName())->toBe('laravel-crm.dashboard');

    expect(fn () => $cached->match(Request::create('http://other.example.org/dashboard')))
        ->toThrow(NotFoundHttpException::class);
});

test('the bare domain and www. redirect to route_domain on GET with path and query', function (string $host) {
    $this->get("http://{$host}/leads?search=fern&page=2")
        ->assertRedirect('http://crm.example.com.au/leads?search=fern&page=2');

    $this->get("https://{$host}/")
        ->assertRedirect('https://crm.example.com.au/');
})->with(['example.com.au', 'www.example.com.au']);

test('HEAD requests redirect too', function () {
    $this->call('HEAD', 'http://example.com.au/dashboard')
        ->assertRedirect('http://crm.example.com.au/dashboard');
});

test('a request that matches no CRM route still redirects', function () {
    $this->get('http://example.com.au/no-such-page')
        ->assertRedirect('http://crm.example.com.au/no-such-page');
});

test('POST on the bare domain is not redirected', function () {
    $response = $this->post('http://example.com.au/login', ['email' => 'a@b.c']);

    expect($response->isRedirect())->toBeFalse();
});

test('API paths on the bare domain are not redirected', function () {
    $response = $this->getJson('http://example.com.au/api/v2/auth/me');

    expect($response->isRedirect())->toBeFalse();
});

test('public /p links on the bare domain are not redirected', function () {
    $response = $this->get('http://example.com.au/p/quotes/does-not-exist');

    expect($response->headers->get('Location'))->toBeNull();
});

test('an unrelated host is not redirected', function () {
    $response = $this->get('http://another-site.com/leads');

    expect($response->isRedirect())->toBeFalse();
});

test('route_domain itself is served, not redirected to itself', function () {
    // Unauthenticated, so the CRM sends it to the login page on the same host.
    $this->get('http://crm.example.com.au/dashboard')
        ->assertRedirect('http://crm.example.com.au/login');

    $this->get('http://crm.example.com.au/login')->assertOk();
});

test('RouteSubdomain compares the full host with route_domain', function () {
    $middleware = new RouteSubdomain;
    $next = fn () => response('ok');

    expect($middleware->handle(Request::create('http://crm.example.com.au/dashboard'), $next)->getContent())->toBe('ok');

    // First label matches route_subdomain, but the host is not route_domain.
    expect(fn () => $middleware->handle(Request::create('http://crm.elsewhere.com/dashboard'), $next))
        ->toThrow(NotFoundHttpException::class);
});

test('the redirect is skipped when route_prefix is set', function () {
    config(['laravel-crm.route_prefix' => 'crm']);

    expect(RouteDomain::redirectFor(Request::create('http://example.com.au/crm/leads')))->toBeNull();
});

test('a non-standard port is kept on the redirect', function () {
    expect(RouteDomain::redirectFor(Request::create('http://example.com.au:8080/leads?x=1')))
        ->toBe('http://crm.example.com.au:8080/leads?x=1');
});

test('sibling hosts are the bare domain and www., never route_domain itself', function () {
    expect(RouteDomain::siblingHosts())->toBe(['example.com.au', 'www.example.com.au']);

    config(['laravel-crm.route_domain' => 'www.example.com']);
    expect(RouteDomain::siblingHosts())->toBe(['example.com']);

    config(['laravel-crm.route_domain' => 'crmapp.com']);
    expect(RouteDomain::siblingHosts())->toBe([]);
});
