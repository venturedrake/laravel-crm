<?php

namespace VentureDrake\LaravelCrm\Http\Controllers;

use App\User;
use DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\StreamedResponse;
use VentureDrake\LaravelCrm\Http\Requests\InviteUserRequest;
use VentureDrake\LaravelCrm\Http\Requests\StoreUserRequest;
use VentureDrake\LaravelCrm\Http\Requests\UpdateUserRequest;
use VentureDrake\LaravelCrm\Models\Address;
use VentureDrake\LaravelCrm\Models\Email;
use VentureDrake\LaravelCrm\Models\Phone;
use VentureDrake\LaravelCrm\Models\Role;
use VentureDrake\LaravelCrm\Models\Team;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        return view('laravel-crm::users.index');
    }

    /**
     * Show the form for inviting a new resource.
     *
     * @return Response
     */
    public function invite()
    {
        return view('laravel-crm::users.invite');
    }

    /**
     * Send invite
     *
     * @param  Request  $request
     * @return Response
     */
    public function sendInvite(InviteUserRequest $request)
    {
        flash(ucfirst(trans('laravel-crm::lang.user_invitation_sent')))->success()->important();

        return redirect(route('laravel-crm.users.index'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $teams = Team::orderBy('name', 'ASC')->get();

        return view('laravel-crm::users.create', [
            'teams' => $teams,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(StoreUserRequest $request)
    {
        $user = User::forceCreate([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'crm_access' => (($request->crm_access == 'on') ? 1 : 0),
        ]);

        if ($request->role) {
            if ($role = Role::find($request->role)) {
                if ($removeRole = $user->roles()->where('crm_role', 1)->first()) { // THIS COULD BE A BUG
                    $user->removeRole($removeRole);
                }

                $user->assignRole($role);
            }
        }

        $this->updateUserPhones($user, $request->phones);
        $this->updateUserEmails($user, $request->emails);
        $this->updateUserAddresses($user, $request->addresses);

        if (config('laravel-crm.teams')) {
            if ($team = auth()->user()->currentTeam) {
                DB::table('team_user')->insert([
                    'team_id' => $team->id,
                    'user_id' => $user->id,
                    'role' => 'editor', // Default Jetstream role
                ]);

                $user->forceFill([
                    'current_team_id' => $team->id,
                ])->save();
            }
        }

        if ($request->user_teams) {
            $user->crmTeams()->sync($request->user_teams);
        } else {
            $user->crmTeams()->sync([]);
        }

        flash(ucfirst(trans('laravel-crm::lang.user_stored')))->success()->important();

        return redirect(route('laravel-crm.users.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show(User $user)
    {
        return view('laravel-crm::users.show', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit(User $user)
    {
        $teams = Team::orderBy('name', 'ASC')->get();

        return view('laravel-crm::users.edit', [
            'user' => $user,
            'teams' => $teams,
            'emails' => $user->emails,
            'phones' => $user->phones,
            'addresses' => $user->addresses,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $user->forceFill([
            'name' => $request->name,
            'email' => $request->email,
            'crm_access' => (($request->crm_access == 'on') ? 1 : 0),
        ])->save();

        $this->updateUserPhones($user, $request->phones);
        $this->updateUserEmails($user, $request->emails);
        $this->updateUserAddresses($user, $request->addresses);

        if ($request->role) {
            if ($role = Role::find($request->role)) {
                if ($removeRole = $user->roles()->where('crm_role', 1)->first()) {
                    $user->removeRole($removeRole);
                }

                $user->assignRole($role);
            }
        }

        if ($request->user_teams) {
            $user->crmTeams()->sync($request->user_teams);
        } else {
            $user->crmTeams()->sync([]);
        }

        flash(ucfirst(trans('laravel-crm::lang.user_updated')))->success()->important();

        return redirect(route('laravel-crm.users.show', $user));
    }

    /**
     * Parse an uploaded CSV and store the result in the session, then redirect
     * back to the import page. File never passes through Livewire.
     *
     * @return RedirectResponse
     */
    public function parseImport(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $path = $request->file('csv_file')->getRealPath();
        $handle = fopen($path, 'r');

        if ($handle === false) {
            return back()->withErrors(['csv_file' => ucfirst(__('laravel-crm::lang.import_file_error'))]);
        }

        $header = fgetcsv($handle);
        $header = array_map(fn ($h) => strtolower(trim($h)), $header);

        $required = ['name', 'email'];
        $missing = array_diff($required, $header);

        if (! empty($missing)) {
            fclose($handle);

            return back()->withErrors([
                'csv_file' => ucfirst(__('laravel-crm::lang.import_missing_columns')).': '.implode(', ', $missing),
            ]);
        }

        $rows = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (count($row) < count($header)) {
                $row = array_pad($row, count($header), null);
            }

            $data = array_combine($header, $row);
            $rowErrors = [];

            if (empty(trim($data['name'] ?? ''))) {
                $rowErrors[] = __('laravel-crm::lang.import_name_required');
            }

            if (empty(trim($data['email'] ?? ''))) {
                $rowErrors[] = __('laravel-crm::lang.import_email_required');
            } elseif (! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $rowErrors[] = __('laravel-crm::lang.import_email_invalid');
            }

            $rows[] = [
                'row' => $rowNumber,
                'name' => trim($data['name'] ?? ''),
                'email' => trim($data['email'] ?? ''),
                'crm_access' => in_array(strtolower(trim($data['crm_access'] ?? '1')), ['1', 'yes', 'true']) ? 1 : 0,
                'role' => trim($data['role'] ?? ''),
                'email_verified_at' => $this->sanitiseDateField($data['email_verified_at'] ?? ''),
                'created_at' => $this->sanitiseDateField($data['created_at'] ?? ''),
                'updated_at' => $this->sanitiseDateField($data['updated_at'] ?? ''),
                'last_online_at' => $this->sanitiseDateField($data['last_online_at'] ?? ''),
                'mailing_list' => in_array(strtolower(trim($data['mailing_list'] ?? '1')), ['1', 'yes', 'true']) ? 1 : 0,
                'errors' => $rowErrors,
            ];
        }

        fclose($handle);

        session()->put('crm_user_import_preview', $rows);

        return redirect()->route('laravel-crm.users.import');
    }

    /**
     * Sanitise an optional date field from the CSV.
     * Returns an empty string for blank / NULL-sentinel / unparseable values.
     */
    private function sanitiseDateField(string $value): string
    {
        $nullSentinels = ['', 'null', 'NULL', 'n/a', 'N/A', 'none', 'NONE', '0', '0000-00-00', '0000-00-00 00:00:00'];

        if (in_array(trim($value), $nullSentinels, true)) {
            return '';
        }

        try {
            \Carbon\Carbon::parse(trim($value));
        } catch (\Throwable) {
            return '';
        }

        return trim($value);
    }

    /**
     * Show the bulk import form.
     *
     * @return Response
     */
    public function import()
    {
        return view('laravel-crm::users.import');
    }

    /**
     * Stream a sample CSV file for users import.
     *
     * @return StreamedResponse
     */
    public function sampleCsv()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users-import-sample.csv"',
        ];

        $rows = [
            ['name', 'email', 'crm_access', 'role', 'email_verified_at', 'created_at', 'updated_at', 'last_online_at', 'mailing_list'],
            ['Jane Smith', 'jane@example.com', '1', 'Admin', '2024-01-15 09:00:00', '2024-01-15 09:00:00', '2024-01-15 09:00:00', '2024-01-15 09:00:00', '1'],
            ['John Doe', 'john@example.com', '1', '', '', '2024-03-10 14:30:00', '2024-03-10 14:30:00', '', '1'],
            ['Alice Brown', 'alice@example.com', '0', '', '', '', '', '', '0'],
        ];

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy(User $user)
    {
        $user->delete();

        flash(ucfirst(trans('laravel-crm::lang.user_deleted')))->success()->important();

        return redirect(route('laravel-crm.users.index'));
    }

    protected function updateUserPhones($user, $phones)
    {
        $phoneIds = [];
        if ($phones) {
            foreach ($phones as $phoneRequest) {
                if ($phoneRequest['id'] && $phone = Phone::find($phoneRequest['id'])) {
                    $phone->update([
                        'number' => $phoneRequest['number'],
                        'type' => $phoneRequest['type'],
                        'primary' => ((isset($phoneRequest['primary']) && $phoneRequest['primary'] == 'on') ? 1 : 0),
                    ]);
                    $phoneIds[] = $phone->id;
                } elseif ($phoneRequest['number']) {
                    $phone = $user->phones()->create([
                        'external_id' => Uuid::uuid4()->toString(),
                        'number' => $phoneRequest['number'],
                        'type' => $phoneRequest['type'],
                        'primary' => ((isset($phoneRequest['primary']) && $phoneRequest['primary'] == 'on') ? 1 : 0),
                    ]);
                    $phoneIds[] = $phone->id;
                }
            }
        }

        foreach ($user->phones as $phone) {
            if (! in_array($phone->id, $phoneIds)) {
                $phone->delete();
            }
        }
    }

    protected function updateUserEmails($user, $emails)
    {
        $emailIds = [];

        if ($emails) {
            foreach ($emails as $emailRequest) {
                if ($emailRequest['id'] && $email = Email::find($emailRequest['id'])) {
                    $email->update([
                        'address' => $emailRequest['address'],
                        'type' => $emailRequest['type'],
                        'primary' => ((isset($emailRequest['primary']) && $emailRequest['primary'] == 'on') ? 1 : 0),
                    ]);

                    $emailIds[] = $email->id;
                } elseif ($emailRequest['address']) {
                    $email = $user->emails()->create([
                        'external_id' => Uuid::uuid4()->toString(),
                        'address' => $emailRequest['address'],
                        'type' => $emailRequest['type'],
                        'primary' => ((isset($emailRequest['primary']) && $emailRequest['primary'] == 'on') ? 1 : 0),
                    ]);

                    $emailIds[] = $email->id;
                }
            }
        }

        foreach ($user->emails as $email) {
            if (! in_array($email->id, $emailIds)) {
                $email->delete();
            }
        }
    }

    protected function updateUserAddresses($user, $addresses)
    {
        $addressIds = [];

        if ($addresses) {
            foreach ($addresses as $addressRequest) {
                if ($addressRequest['id'] && $address = Address::find($addressRequest['id'])) {
                    $address->update([
                        'address_type_id' => $addressRequest['type'] ?? null,
                        'address' => $addressRequest['address'] ?? null,
                        'name' => $addressRequest['name'] ?? null,
                        'contact' => $addressRequest['contact'] ?? null,
                        'phone' => $addressRequest['phone'] ?? null,
                        'line1' => $addressRequest['line1'],
                        'line2' => $addressRequest['line2'],
                        'line3' => $addressRequest['line3'],
                        'city' => $addressRequest['city'],
                        'state' => $addressRequest['state'],
                        'code' => $addressRequest['code'],
                        'country' => $addressRequest['country'],
                        'primary' => ((isset($addressRequest['primary']) && $addressRequest['primary'] == 'on') ? 1 : 0),
                    ]);

                    $addressIds[] = $address->id;
                } else {
                    $address = $user->addresses()->create([
                        'external_id' => Uuid::uuid4()->toString(),
                        'address_type_id' => $addressRequest['type'] ?? null,
                        'address' => $addressRequest['address'] ?? null,
                        'name' => $addressRequest['name'] ?? null,
                        'contact' => $addressRequest['contact'] ?? null,
                        'phone' => $addressRequest['phone'] ?? null,
                        'line1' => $addressRequest['line1'],
                        'line2' => $addressRequest['line2'],
                        'line3' => $addressRequest['line3'],
                        'city' => $addressRequest['city'],
                        'state' => $addressRequest['state'],
                        'code' => $addressRequest['code'],
                        'country' => $addressRequest['country'],
                        'primary' => ((isset($addressRequest['primary']) && $addressRequest['primary'] == 'on') ? 1 : 0),
                    ]);

                    $addressIds[] = $address->id;
                }
            }
        }

        foreach ($user->addresses as $address) {
            if (! in_array($address->id, $addressIds)) {
                $address->delete();
            }
        }
    }
}
