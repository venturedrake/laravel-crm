<?php

/**
 * Tests for the User-model file-patching logic inside LaravelCrmInstall::patchUserModel().
 */
beforeEach(function () {
    $this->tmpDir = sys_get_temp_dir().'/laravel-crm-patch-tests-'.uniqid();
    mkdir($this->tmpDir, 0755, true);
});

afterEach(function () {
    foreach (glob($this->tmpDir.'/*') as $f) {
        unlink($f);
    }
    rmdir($this->tmpDir);
});

function applyPatch(string $contents): string
{
    $traits = [
        'VentureDrake\LaravelCrm\Traits\HasCrmAccess' => 'HasCrmAccess',
        'VentureDrake\LaravelCrm\Traits\HasCrmTeams' => 'HasCrmTeams',
        'Spatie\Permission\Traits\HasRoles' => 'HasRoles',
    ];

    preg_match('/\bclass\s+(\w+)\b/', $contents, $classMatch);
    $className = $classMatch[1];

    foreach ($traits as $fqcn => $shortName) {
        $hasImport = (bool) preg_match('/^use\s+'.preg_quote($fqcn, '/').'\s*;/m', $contents);
        $hasUseInClass = (bool) preg_match(
            '/class\s+'.preg_quote($className, '/').'\b[^{]*\{[^}]*?\buse\s+[^;]*\b'.preg_quote($shortName, '/').'\b[^;]*;/s',
            $contents
        );

        if ($hasImport && $hasUseInClass) {
            continue;
        }

        if (! $hasImport) {
            $contents = preg_replace(
                '/^(namespace[^;]+;\s*\R)/m',
                "$1\nuse {$fqcn};\n",
                $contents,
                1
            );
        }

        if (! $hasUseInClass) {
            $classBodyPattern = '/(class\s+'.preg_quote($className, '/').'\b[^{]*\{\s*)/';

            if (preg_match('/(class\s+'.preg_quote($className, '/').'\b[^{]*\{\s*)(use\s+([^;]+);)/', $contents, $m)) {
                $existing = trim($m[3]);
                $newUse = 'use '.$existing.', '.$shortName.';';
                $contents = str_replace($m[2], $newUse, $contents);
            } else {
                $contents = preg_replace(
                    $classBodyPattern,
                    "$1    use {$shortName};\n\n    ",
                    $contents,
                    1
                );
            }
        }
    }

    return $contents;
}

test('patch adds all three imports to clean model', function () {
    $original = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;
}
PHP;

    $patched = applyPatch($original);

    expect($patched)
        ->toContain('use VentureDrake\LaravelCrm\Traits\HasCrmAccess;')
        ->toContain('use VentureDrake\LaravelCrm\Traits\HasCrmTeams;')
        ->toContain('use Spatie\Permission\Traits\HasRoles;');
});

test('patch adds traits to existing use statement in class body', function () {
    $original = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;
}
PHP;

    $patched = applyPatch($original);

    expect($patched)
        ->toContain('HasCrmAccess')
        ->toContain('HasCrmTeams')
        ->toContain('HasRoles');
});

test('patch is idempotent when all traits already present', function () {
    $original = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use VentureDrake\LaravelCrm\Traits\HasCrmAccess;
use VentureDrake\LaravelCrm\Traits\HasCrmTeams;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasCrmAccess, HasCrmTeams, HasRoles;
}
PHP;

    expect(applyPatch($original))->toBe($original);
});

test('patch does not duplicate an existing import', function () {
    $original = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use VentureDrake\LaravelCrm\Traits\HasCrmAccess;

class User extends Authenticatable
{
    use HasFactory;
}
PHP;

    expect(substr_count(applyPatch($original), 'use VentureDrake\LaravelCrm\Traits\HasCrmAccess;'))->toBe(1);
});

test('patch preserves existing namespace declaration', function () {
    $original = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
}
PHP;

    expect(applyPatch($original))->toContain('namespace App\Models;');
});

test('patch inserts new use line when class body has no existing traits', function () {
    $original = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = ['name', 'email'];
}
PHP;

    $patched = applyPatch($original);

    expect($patched)
        ->toContain('HasCrmAccess')
        ->toContain('HasCrmTeams')
        ->toContain('HasRoles');
});

test('patch handles model with multiple existing trait imports', function () {
    $original = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
}
PHP;

    $patched = applyPatch($original);

    expect($patched)
        ->toContain('HasCrmAccess')
        ->toContain('HasCrmTeams')
        ->toContain('HasRoles')
        ->toContain('HasApiTokens')
        ->toContain('Notifiable');
});

test('patch uses short trait name inside class body', function () {
    $original = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;
}
PHP;

    $patched = applyPatch($original);

    expect($patched)
        ->not->toContain('use VentureDrake\LaravelCrm\Traits\HasCrmAccess,')
        ->toContain('HasCrmAccess');
});
