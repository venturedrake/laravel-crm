<?php

use VentureDrake\LaravelCrm\Support\XeroIntegration;

/*
 * `dcblogdev/laravel-xero` is a *suggested* dependency, not a required one.
 *
 * It sat in `require` until it made the package uninstallable: every version it
 * has ever published caps `guzzlehttp/guzzle` at `^7.9.3`, so a stock Laravel
 * 13.32+ app — which allows `^7.8.2 || ^8.0` and so resolves Guzzle 8 —
 * couldn't `composer require venturedrake/laravel-crm` at all. Composer
 * surfaced the clash against *our* Guzzle constraint first, which made it look
 * like a one-line bump of our own `require`; it wasn't, because the real cap
 * arrived transitively through Xero.
 *
 * Moving it to `suggest` means `Dcblogdev\*` classes may be absent at runtime,
 * and every eager reference to one is a fatal error when they are. The tests
 * below pin both halves of that: the dependency stays out of `require`, and no
 * file reaches a `Dcblogdev\` class without gating on XeroIntegration.
 */

/*
 * The scanner below is the regression guard, so it is written to be checked
 * rather than trusted — every property claimed here has a fixture under
 * `laravel-crm-xero-scanner-is-not-vacuous`.
 *
 * Comments are stripped once, up front, with PHP's own tokenizer, and *both*
 * the reference scan and the guard check run against that stripped copy. Doing
 * it per-line in one and not the other is how the earlier versions went wrong:
 * a `/*` opened after code on the same line was scanned as live code, and a
 * file whose only mention of `XeroIntegration::connected()` was in a comment
 * counted as gated. The tokenizer also gets the cases hand-rolled matching
 * kept missing — `/*` inside a string literal, and PHP 8 `#[Attribute]` lines,
 * which are not comments and *are* resolved eagerly.
 *
 * What it catches: any file that reaches a `Dcblogdev\` class — fully
 * qualified, or through a plain, aliased, or grouped `use` import — without
 * gating on XeroIntegration in live code somewhere in the file. An import form
 * the alias parser does not understand is itself reported, so the scanner
 * fails loudly rather than silently skipping a file it cannot read.
 *
 * What it does not catch: a file that keeps a legitimate guarded call *and*
 * gains a second unguarded one. A per-line guard proof needs real control-flow
 * analysis; stating the weaker guarantee honestly beats implying the stronger
 * one.
 */

if (! function_exists('laravelCrmXeroStripComments')) {
    /**
     * The same source with every comment blanked out, line numbering intact.
     *
     * Blade comments go first, because `token_get_all()` sees everything
     * outside `<?php` as inline HTML and would hand them back untouched.
     * Comment bodies are replaced by their own newline count so that hits
     * still report the line number they occupy in the real file.
     */
    function laravelCrmXeroStripComments(string $contents): string
    {
        $blankLines = fn (string $text): string => str_repeat("\n", substr_count($text, "\n"));

        $contents = preg_replace_callback(
            '/\{\{--.*?--\}\}/s',
            fn (array $matches): string => $blankLines($matches[0]),
            $contents
        );

        $stripped = '';

        foreach (@token_get_all($contents) as $token) {
            if (! is_array($token)) {
                $stripped .= $token;

                continue;
            }

            $stripped .= in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)
                ? $blankLines($token[1])
                : $token[1];
        }

        return $stripped;
    }
}

if (! function_exists('laravelCrmXeroAliases')) {
    /**
     * Local names bound to a `Dcblogdev\` class by the file's `use` statements.
     *
     * Covers `use Dcblogdev\Xero\Facades\Xero;`, the `as` form, and the grouped
     * `use Dcblogdev\Xero\{Facades\Xero, Models\XeroToken};` form on one line or
     * several. Expects comment-free input.
     *
     * @return array<int, string>
     */
    function laravelCrmXeroAliases(string $code): array
    {
        $aliases = [];

        $localName = function (string $path, ?string $alias): string {
            if ($alias !== null && $alias !== '') {
                return $alias;
            }

            $separator = strrpos($path, '\\');

            return $separator === false ? $path : substr($path, $separator + 1);
        };

        // use Dcblogdev\Xero\{Facades\Xero, Models\XeroToken as Token};
        if (preg_match_all('/\buse\s+(Dcblogdev(?:\\\\[A-Za-z0-9_]+)*)\\\\\{([^}]*)\}\s*;/', $code, $groups, PREG_SET_ORDER)) {
            foreach ($groups as $group) {
                foreach (explode(',', $group[2]) as $member) {
                    $member = trim($member);

                    if ($member === '') {
                        continue;
                    }

                    $parts = preg_split('/\s+as\s+/i', $member);
                    $aliases[] = $localName(trim($parts[0]), isset($parts[1]) ? trim($parts[1]) : null);
                }
            }
        }

        // use Dcblogdev\Xero\Facades\Xero; / ... as Accounting;
        if (preg_match_all('/\buse\s+(?:function\s+|const\s+)?(Dcblogdev\\\\[A-Za-z0-9_\\\\]+?)(?:\s+as\s+([A-Za-z0-9_]+))?\s*;/', $code, $singles, PREG_SET_ORDER)) {
            foreach ($singles as $single) {
                $aliases[] = $localName($single[1], $single[2] ?? null);
            }
        }

        return array_values(array_unique($aliases));
    }
}

if (! function_exists('laravelCrmXeroOffenders')) {
    /**
     * Every ungated reference to a `Dcblogdev\` class in a chunk of PHP or Blade.
     *
     * A reference is "eager" — and so fatal when the package is absent — if it
     * is resolved as the line runs: a static call, a `::class` constant, `new`,
     * or `instanceof`. Imports and parameter type hints are deliberately
     * excluded, because PHP resolves neither at class-declaration time; that is
     * exactly why `XeroTokenObserver` is safe with eight `XeroToken $token`
     * hints and no guard of its own.
     *
     * @return array<int, string> `'<line number> — <source line>'` per hit
     */
    function laravelCrmXeroOffenders(string $contents): array
    {
        $code = laravelCrmXeroStripComments($contents);

        // Checked against stripped source, so a comment that merely names the
        // gate cannot stand in for actually calling it.
        if (str_contains($code, 'XeroIntegration::installed(') || str_contains($code, 'XeroIntegration::connected(')) {
            return [];
        }

        $aliases = laravelCrmXeroAliases($code);
        $hits = [];

        if ($aliases === [] && preg_match('/\buse\s+[^;]*Dcblogdev/', $code)) {
            $hits[] = '0 — unreadable Dcblogdev import; teach laravelCrmXeroAliases() this form rather than letting the file skip the check';
        }

        $patterns = ['/Dcblogdev\\\\/'];

        foreach ($aliases as $alias) {
            $quoted = preg_quote($alias, '/');

            // `\bXero\s*::` cannot match `XeroIntegration::`, and
            // `\bXeroToken\s*::` cannot match `XeroTokenObserver::class`.
            $patterns[] = '/\b'.$quoted.'\s*::/';
            $patterns[] = '/\bnew\s+'.$quoted.'\b/';
            $patterns[] = '/\binstanceof\s+'.$quoted.'\b/';
        }

        foreach (preg_split("/\r\n|\n|\r/", $code) as $index => $line) {
            $trimmed = trim($line);

            if (str_starts_with($trimmed, 'use ') || str_starts_with($trimmed, 'namespace ')) {
                continue;
            }

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $trimmed)) {
                    $hits[] = ($index + 1).' — '.$trimmed;

                    break;
                }
            }
        }

        return $hits;
    }
}

test('dcblogdev/laravel-xero is not a hard dependency', function () {
    $composer = json_decode(file_get_contents(__DIR__.'/../../../composer.json'), true);

    expect($composer['require'])->not->toHaveKey('dcblogdev/laravel-xero');
    expect($composer['suggest'])->toHaveKey('dcblogdev/laravel-xero');
});

test('laravel-crm-xero-scanner-is-not-vacuous', function (string $source, bool $shouldFlag) {
    expect(laravelCrmXeroOffenders($source) !== [])->toBe($shouldFlag);
})->with([
    // The regression that matters: the short alias, no guard. A scanner that
    // matched only the literal string `Dcblogdev` missed this entirely.
    'unguarded static call through an imported alias' => [
        <<<'PHP'
        <?php
        use Dcblogdev\Xero\Facades\Xero;
        class InvoiceService {
            public function create() {
                if (Xero::isConnected()) {
                    Xero::invoices()->store([]);
                }
            }
        }
        PHP,
        true,
    ],
    'unguarded aliased import' => [
        <<<'PHP'
        <?php
        use Dcblogdev\Xero\Facades\Xero as Accounting;
        class Sync { public function run() { Accounting::post('Items', []); } }
        PHP,
        true,
    ],
    'unguarded grouped import' => [
        <<<'PHP'
        <?php
        use Dcblogdev\Xero\{Facades\Xero, Models\XeroToken};
        class Sync { public function run() { return Xero::post('Items', []); } }
        PHP,
        true,
    ],
    'unguarded grouped import spread over several lines' => [
        <<<'PHP'
        <?php
        use Dcblogdev\Xero\{
            Facades\Xero,
            Models\XeroToken as Token,
        };
        class Sync { public function run() { return Token::first(); } }
        PHP,
        true,
    ],
    'unguarded new on a Dcblogdev model' => [
        <<<'PHP'
        <?php
        use Dcblogdev\Xero\Models\XeroToken;
        class Sync { public function run() { return new XeroToken; } }
        PHP,
        true,
    ],
    'unguarded fully qualified reference, as a Blade partial would write it' => [
        '@if(! \Dcblogdev\Xero\Facades\Xero::isConnected())',
        true,
    ],
    // `#[...]` is an attribute, not a comment, and reflection resolves it.
    'unguarded reference inside a PHP attribute' => [
        <<<'PHP'
        <?php
        #[ObservedBy(\Dcblogdev\Xero\Models\XeroToken::class)]
        class Sync {}
        PHP,
        true,
    ],
    // A guard named only in prose is not a guard.
    'a line comment naming the gate does not stand in for calling it' => [
        <<<'PHP'
        <?php
        use Dcblogdev\Xero\Facades\Xero;
        class Sync {
            public function run() {
                // Previously wrapped in XeroIntegration::connected() but inlined for speed.
                return Xero::post('Items', []);
            }
        }
        PHP,
        true,
    ],
    'a docblock naming the gate does not stand in for calling it' => [
        <<<'PHP'
        <?php
        use Dcblogdev\Xero\Facades\Xero;
        class Sync {
            /**
             * Callers must ensure XeroIntegration::connected() first.
             */
            public function run() { return Xero::post('Items', []); }
        }
        PHP,
        true,
    ],
    'an import form the alias parser cannot read is reported, not skipped' => [
        <<<'PHP'
        <?php
        use Dcblogdev\Xero\Facades\{Xero};
        class Sync { public function run() { return Xero::post('Items', []); } }
        PHP,
        true,
    ],
    'the same call once it goes through the gate' => [
        <<<'PHP'
        <?php
        use Dcblogdev\Xero\Facades\Xero;
        use VentureDrake\LaravelCrm\Support\XeroIntegration;
        class InvoiceService {
            public function create() {
                if (XeroIntegration::connected()) {
                    Xero::invoices()->store([]);
                }
            }
        }
        PHP,
        false,
    ],
    'a guarded grouped import' => [
        <<<'PHP'
        <?php
        use Dcblogdev\Xero\{Facades\Xero, Models\XeroToken};
        use VentureDrake\LaravelCrm\Support\XeroIntegration;
        class Sync {
            public function run() {
                return XeroIntegration::connected() ? Xero::post('Items', []) : null;
            }
        }
        PHP,
        false,
    ],
    // XeroTokenObserver's shape: imports and type hints only, both lazy.
    'import and parameter type hints without any eager reference' => [
        <<<'PHP'
        <?php
        use Dcblogdev\Xero\Models\XeroToken;
        class XeroTokenObserver {
            public function creating(XeroToken $xeroToken) { $xeroToken->team_id = 1; }
        }
        PHP,
        false,
    ],
    'a commented-out reference is inert' => [
        <<<'PHP'
        <?php
        use Dcblogdev\Xero\Facades\Xero;
        class StoreInvoiceRequest {
            public function rules() {
                /*if (! Xero::isConnected()) {
                    $rules['number'] = 'required';
                }*/
                return [];
            }
        }
        PHP,
        false,
    ],
    'a block comment opened after code on the same line is still a comment' => [
        <<<'PHP'
        <?php
        use Dcblogdev\Xero\Facades\Xero;
        class Sync {
            public function run() {
                $x = 1; /* legacy path, kept for reference:
                Xero::isConnected();
                */
                return $x;
            }
        }
        PHP,
        false,
    ],
    // If the `/*` in the string were read as a comment opener it would swallow
    // the rest of the file and the call below would go unreported.
    'a string that looks like a comment opener does not swallow the file' => [
        <<<'PHP'
        <?php
        use Dcblogdev\Xero\Facades\Xero;
        class Sync {
            public function run() {
                $marker = '/* not a comment';
                return Xero::post('Items', []);
            }
        }
        PHP,
        true,
    ],
    // Deliberate: `app('Dcblogdev\Xero\Facades\Xero')` and `new $class` reach
    // the class just as fatally as a literal one, so naming it in a string is
    // reported rather than waved through.
    'a class named in a string literal counts as a reference' => [
        <<<'PHP'
        <?php
        class Sync {
            public function run() { return app('Dcblogdev\Xero\Facades\Xero'); }
        }
        PHP,
        true,
    ],
    'XeroIntegration is not mistaken for the Xero facade' => [
        <<<'PHP'
        <?php
        use VentureDrake\LaravelCrm\Support\XeroIntegration;
        class Thing { public function run() { return XeroIntegration::installed(); } }
        PHP,
        false,
    ],
]);

test('hits report the line number they occupy in the original file', function () {
    $source = <<<'PHP'
    <?php
    use Dcblogdev\Xero\Facades\Xero;

    /**
     * A docblock that pushes the call down the file.
     */
    class Sync {
        public function run() { return Xero::post('Items', []); }
    }
    PHP;

    expect(laravelCrmXeroOffenders($source))->toBe([
        "8 — public function run() { return Xero::post('Items', []); }",
    ]);
});

test('no file reaches a Dcblogdev class without gating on XeroIntegration', function () {
    $root = realpath(__DIR__.'/../../..');

    $offenders = [];

    foreach (['src', 'resources'] as $dir) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root.'/'.$dir, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $path = str_replace($root.'/', '', $file->getPathname());

            // The gate itself is the one place allowed to name the classes.
            if ($path === 'src/Support/XeroIntegration.php') {
                continue;
            }

            foreach (laravelCrmXeroOffenders(file_get_contents($file->getPathname())) as $hit) {
                $offenders[] = $path.':'.$hit;
            }
        }
    }

    expect($offenders)->toBe([], implode("\n", array_merge(
        ['Found an ungated reference to a Dcblogdev class. Route it through XeroIntegration::installed()/connected() instead:'],
        $offenders
    )));
});

test('connected() is false when Xero reports no connection', function () {
    // TestCase binds a stub on the `xero` container key whose isConnected()
    // returns false, standing in for an installed-but-unconnected host app.
    expect(XeroIntegration::connected())->toBeFalse();
});

test('connected() is false whenever the package is absent', function () {
    // The suite installs dcblogdev/laravel-xero as a dev dependency, so
    // installed() is true here and the absent branch can't be exercised
    // directly. Pin the implication instead: connected() must never report
    // true without installed() also being true, because every caller treats a
    // true result as a licence to touch the Dcblogdev classes.
    expect(XeroIntegration::connected() && ! XeroIntegration::installed())->toBeFalse();
});
