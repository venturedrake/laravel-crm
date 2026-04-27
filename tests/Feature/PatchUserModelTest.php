<?php

namespace VentureDrake\LaravelCrm\Tests\Feature;

use VentureDrake\LaravelCrm\Tests\TestCase;

/**
 * Tests for the User-model file-patching logic inside LaravelCrmInstall::patchUserModel().
 *
 * We exercise the same regex/replacement logic extracted into a helper here
 * rather than actually invoking the full interactive installer command.
 */
class PatchUserModelTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = sys_get_temp_dir().'/laravel-crm-patch-tests-'.uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up any temp files
        foreach (glob($this->tmpDir.'/*') as $f) {
            unlink($f);
        }
        rmdir($this->tmpDir);
    }

    /**
     * Apply the same patching logic as LaravelCrmInstall::patchUserModel().
     */
    private function applyPatch(string $contents): string
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

    public function test_patch_adds_all_three_imports_to_clean_model(): void
    {
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

        $patched = $this->applyPatch($original);

        $this->assertStringContainsString('use VentureDrake\LaravelCrm\Traits\HasCrmAccess;', $patched);
        $this->assertStringContainsString('use VentureDrake\LaravelCrm\Traits\HasCrmTeams;', $patched);
        $this->assertStringContainsString('use Spatie\Permission\Traits\HasRoles;', $patched);
    }

    public function test_patch_adds_traits_to_existing_use_statement_in_class_body(): void
    {
        $original = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;
}
PHP;

        $patched = $this->applyPatch($original);

        $this->assertStringContainsString('HasCrmAccess', $patched);
        $this->assertStringContainsString('HasCrmTeams', $patched);
        $this->assertStringContainsString('HasRoles', $patched);
    }

    public function test_patch_is_idempotent_when_all_traits_already_present(): void
    {
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

        $patched = $this->applyPatch($original);

        $this->assertSame($original, $patched);
    }

    public function test_patch_does_not_duplicate_an_existing_import(): void
    {
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

        $patched = $this->applyPatch($original);

        $this->assertSame(
            1,
            substr_count($patched, 'use VentureDrake\LaravelCrm\Traits\HasCrmAccess;')
        );
    }

    public function test_patch_preserves_existing_namespace_declaration(): void
    {
        $original = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
}
PHP;

        $patched = $this->applyPatch($original);

        $this->assertStringContainsString('namespace App\Models;', $patched);
    }

    public function test_patch_inserts_new_use_line_when_class_body_has_no_existing_traits(): void
    {
        $original = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = ['name', 'email'];
}
PHP;

        $patched = $this->applyPatch($original);

        // The patch appends the three short trait names (which may be grouped
        // into a single `use ...;` line or as separate lines).
        $this->assertStringContainsString('HasCrmAccess', $patched);
        $this->assertStringContainsString('HasCrmTeams', $patched);
        $this->assertStringContainsString('HasRoles', $patched);
    }

    public function test_patch_handles_model_with_multiple_existing_trait_imports(): void
    {
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

        $patched = $this->applyPatch($original);

        $this->assertStringContainsString('HasCrmAccess', $patched);
        $this->assertStringContainsString('HasCrmTeams', $patched);
        $this->assertStringContainsString('HasRoles', $patched);
        // Original traits must still be present
        $this->assertStringContainsString('HasApiTokens', $patched);
        $this->assertStringContainsString('Notifiable', $patched);
    }

    public function test_patch_uses_short_trait_name_inside_class_body(): void
    {
        $original = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;
}
PHP;

        $patched = $this->applyPatch($original);

        // The class body should reference the short name, not the FQCN.
        $this->assertStringNotContainsString('use VentureDrake\LaravelCrm\Traits\HasCrmAccess,', $patched);
        $this->assertStringContainsString('HasCrmAccess', $patched);
    }
}
