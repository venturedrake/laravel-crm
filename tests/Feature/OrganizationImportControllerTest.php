<?php

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use VentureDrake\LaravelCrm\Http\Controllers\OrganizationController;
use VentureDrake\LaravelCrm\Services\OrganizationService;

function uploadOrgCsv(string $contents, string $filename = 'import.csv'): UploadedFile
{
    $tmp = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($tmp, $contents);

    return new UploadedFile($tmp, $filename, 'text/csv', null, true);
}

function makeOrganizationController(): OrganizationController
{
    return new OrganizationController(app(OrganizationService::class));
}

test('sample csv stream contains the expected headers', function () {
    $response = makeOrganizationController()->sampleCsv();

    ob_start();
    $response->sendContent();
    $body = ob_get_clean();

    expect($body)->toContain('name,email,phone,website_url,vat_number,description');
    expect($body)->toContain('Acme Inc');
});

test('parseImport stores parsed rows in the session', function () {
    $csv = "name,email,phone,website_url\n".
           "Acme,hello@acme.com,5551234,https://acme.com\n".
           "Globex,,,\n";

    $request = Request::create('/import', 'POST');
    $request->files->set('csv_file', uploadOrgCsv($csv));

    $response = makeOrganizationController()->parseImport($request);

    expect($response->getTargetUrl())->toContain('organizations/import');

    $rows = session('crm_organization_import_preview');
    expect($rows)->toHaveCount(2);
    expect($rows[0]['name'])->toBe('Acme');
    expect($rows[0]['email'])->toBe('hello@acme.com');
    expect($rows[0]['website_url'])->toBe('https://acme.com');
    expect($rows[0]['errors'])->toBe([]);
    expect($rows[1]['name'])->toBe('Globex');
    expect($rows[1]['errors'])->toBe([]);
});

test('parseImport flags missing name and invalid email as errors', function () {
    $csv = "name,email\n".
           ",x\n".
           "Acme,bad\n";

    $request = Request::create('/import', 'POST');
    $request->files->set('csv_file', uploadOrgCsv($csv));

    makeOrganizationController()->parseImport($request);

    $rows = session('crm_organization_import_preview');
    expect($rows)->toHaveCount(2);
    expect($rows[0]['errors'])->not->toBeEmpty();
    expect($rows[1]['errors'])->not->toBeEmpty();
});

test('parseImport rejects a CSV missing the required name column', function () {
    $csv = "email,phone\nfoo@bar.com,5551234\n";

    $request = Request::create('/import', 'POST');
    $request->files->set('csv_file', uploadOrgCsv($csv));

    $response = makeOrganizationController()->parseImport($request);

    expect(session()->has('crm_organization_import_preview'))->toBeFalse();
    expect($response->getSession()->get('errors'))->not->toBeNull();
});

