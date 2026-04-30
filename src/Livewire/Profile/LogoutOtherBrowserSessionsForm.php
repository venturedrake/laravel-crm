<?php

namespace VentureDrake\LaravelCrm\Livewire\Profile;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use Livewire\Component;
use Mary\Traits\Toast;

class LogoutOtherBrowserSessionsForm extends Component
{
    use Toast;

    public $password = '';

    public $confirmingLogout = false;

    public function confirmLogout()
    {
        $this->password = '';
        $this->confirmingLogout = true;
    }

    public function logoutOtherBrowserSessions()
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        Auth::logoutOtherDevices($this->password);

        $this->deleteOtherSessionRecords();

        $this->confirmingLogout = false;
        $this->password = '';

        $this->success(ucfirst(__('laravel-crm::lang.logged_out_other_sessions')));
    }

    /**
     * Delete other session records from the sessions table.
     */
    protected function deleteOtherSessionRecords()
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
            ->where('user_id', auth()->user()->getAuthIdentifier())
            ->where('id', '!=', request()->session()->getId())
            ->delete();
    }

    public function getSessionsProperty()
    {
        if (config('session.driver') !== 'database') {
            return collect();
        }

        return collect(
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                ->where('user_id', auth()->user()->getAuthIdentifier())
                ->orderBy('last_activity', 'desc')
                ->get()
        )->map(function ($session) {
            $agent = $this->createAgent($session);

            return (object) [
                'agent' => [
                    'is_desktop' => $agent->isDesktop(),
                    'platform' => $agent->platform(),
                    'browser' => $agent->browser(),
                ],
                'ip_address' => $session->ip_address,
                'is_current_device' => $session->id === request()->session()->getId(),
                'last_active' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
            ];
        });
    }

    protected function createAgent($session)
    {
        if (class_exists(Agent::class)) {
            return tap(new Agent, fn ($agent) => $agent->setUserAgent($session->user_agent));
        }

        // Minimal fallback when jenssegers/agent is not installed
        return new class($session->user_agent)
        {
            public function __construct(protected ?string $userAgent) {}

            public function isDesktop(): bool
            {
                return ! Str::contains((string) $this->userAgent, ['Mobile', 'Android', 'iPhone']);
            }

            public function platform(): string
            {
                foreach (['Windows', 'Macintosh', 'Linux', 'iPhone', 'iPad', 'Android'] as $os) {
                    if (Str::contains((string) $this->userAgent, $os)) {
                        return $os;
                    }
                }

                return 'Unknown';
            }

            public function browser(): string
            {
                foreach (['Edg' => 'Edge', 'Chrome' => 'Chrome', 'Safari' => 'Safari', 'Firefox' => 'Firefox', 'OPR' => 'Opera'] as $needle => $name) {
                    if (Str::contains((string) $this->userAgent, $needle)) {
                        return $name;
                    }
                }

                return 'Unknown';
            }
        };
    }

    public function render()
    {
        return view('laravel-crm::livewire.profile.logout-other-browser-sessions-form');
    }
}
