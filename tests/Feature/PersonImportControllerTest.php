<?php

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use VentureDrake\LaravelCrm\Http\Controllers\PersonController;
use VentureDrake\LaravelCrm\Services\PersonService;

function uploadCsv(string $contents, string $filename = 'import.csv'): UploadedFile
{
    $tmp = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($tmp, $contents);

    return new UploadedFile($tmp, $filename, 'text/csv', null, true);
}

function makePersonController(): PersonController
{
    return new PersonController(app(PersonService::class));
}

test('sample csv stream contains the expected headers', function () {
    $response = makePersonController()->sampleCsv();

    ob_start();
    $response->sendContent();
    $body = ob_get_clean();

    expect($body)->toContain('first_name,last_name,title,email,phone,organization_name,description');
    expect($body)->toContain('Jane,Smith');
});

test('parseImport stores parsed rows in the session', function () {
    $csv = "first_name,last_name,email,phone,organization_name\n".
           "Jane,Smith,jane@example.com,5551234,Acme\n".
           "John,Doe,,,\n";

    $request = Request::create('/import', 'POST');
    $request->files->set('csv_file', uploadCsv($csv));

    $response = makePersonController()->parseImport($request);

    expect($response->getTargetUrl())->toContain('people/import');

    $rows = session('crm_person_import_preview');
    expect($rows)->toHaveCount(2);
    expect($rows[0]['first_name'])->toBe('Jane');
    expect($rows[0]['email'])->toBe('jane@example.com');
    expect($rows[0]['organization_name'])->toBe('Acme');
    expect($rows[0]['errors'])->toBe([]);
    expect($rows[1]['first_name'])->toBe('John');
    expect($rows[1]['errors'])->toBe([]);
});

test('parseImport flags missing first_name and invalid email as errors', function () {
    $csv = "first_name,email\n".
           ",bad\n".
           "Jane,not-an-email\n";

    $request = Request::create('/import', 'POST');
    $request->files->set('csv_file', uploadCsv($csv));

    makePersonController()->parseImport($request);

    $rows = session('crm_person_import_preview');
    expect($rows)->toHaveCount(2);
    expect($rows[0]['errors'])->not->toBeEmpty();
    expect($rows[1]['errors'])->not->toBeEmpty();
});

test('parseImport rejects a CSV missing the required first_name column', function () {
    $csv = "email,phone\nfoo@bar.com,5551234\n";

    $request = Request::create('/import', 'POST');
    $request->files->set('csv_file', uploadCsv($csv));

    $response = makePersonController()->parseImport($request);

    expect(session()->has('crm_person_import_preview'))->toBeFalse();
    expect($response->getSession()->get('errors'))->not->toBeNull();
});

