<?php

use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use VentureDrake\LaravelCrm\Http\Middleware\SetApiTeamContext;

function setApiTeamContextRequest(?string $teamHeader = null): Request
{
    $request = Request::create('/crm/api/v2/leads');

    if ($teamHeader !== null) {
        $request->headers->set('X-Team-ID', $teamHeader);
    }

    return $request;
}

test('passes through when no X-Team-ID header is present', function () {
    $middleware = new SetApiTeamContext;
    $request = setApiTeamContextRequest();

    $response = $middleware->handle($request, fn () => response('ok'));

    expect($response->getContent())->toBe('ok');
});

test('returns 401 when X-Team-ID header is present but user is unauthenticated', function () {
    $middleware = new SetApiTeamContext;
    $request = setApiTeamContextRequest('99');

    $response = $middleware->handle($request, fn () => response('ok'));

    expect($response->getStatusCode())->toBe(401);
});

test('returns 403 when user is not a member of the requested team', function () {
    config()->set('laravel-crm.teams', true);

    $user = new class
    {
        public $currentTeam = null;

        public function allTeams()
        {
            return collect([(object) ['id' => 1], (object) ['id' => 2]]);
        }

        public function setRelation($name, $value)
        {
            $this->{$name} = $value;

            return $this;
        }
    };

    $middleware = new SetApiTeamContext;
    $request = setApiTeamContextRequest('99');
    $request->setUserResolver(fn () => $user);

    $response = $middleware->handle($request, fn () => response('ok'));

    expect($response->getStatusCode())->toBe(403);
});

test('sets currentTeam relation and PermissionRegistrar team id on a valid header', function () {
    config()->set('laravel-crm.teams', true);

    $team = (object) ['id' => 7];
    $user = new class($team)
    {
        public $currentTeam = null;

        protected $team;

        public function __construct($team)
        {
            $this->team = $team;
        }

        public function allTeams()
        {
            return collect([$this->team, (object) ['id' => 99]]);
        }

        public function setRelation($name, $value)
        {
            $this->{$name} = $value;

            return $this;
        }
    };

    $middleware = new SetApiTeamContext;
    $request = setApiTeamContextRequest('7');
    $request->setUserResolver(fn () => $user);

    $response = $middleware->handle($request, fn () => response('ok'));

    expect($response->getContent())->toBe('ok');
    expect($user->currentTeam)->toBe($team);
    expect(app(PermissionRegistrar::class)->getPermissionsTeamId())->toBe(7);
});
