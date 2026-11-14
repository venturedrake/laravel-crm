<?php

// Install an isolated dependency set without changing the package's vendor tree.
$livewire = $argv[1] ?? '3';
$laravel = $argv[2] ?? '12';
if (! in_array($livewire, ['3', '4'], true) || ! in_array($laravel, ['11', '12', '13'], true)) {
    throw new InvalidArgumentException('Usage: php tests/browser/install-matrix.php <Livewire 3|4> <Laravel 11|12|13>');
}
$root = dirname(__DIR__, 2);
$directory = $root.'/.context/browser-dependencies/lw'.$livewire.'-laravel'.$laravel;
if (! is_dir($directory)) {
    mkdir($directory, 0777, true);
}
$composer = json_decode(file_get_contents($root.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
$composer['require']['livewire/livewire'] = '^'.$livewire.'.0';
$composer['require']['laravel/framework'] = '^'.$laravel.'.0';
unset($composer['require-dev'], $composer['autoload-dev'], $composer['scripts'], $composer['extra']);
foreach ($composer['autoload']['psr-4'] as &$path) {
    $path = $root.'/'.$path;
}
unset($path);
foreach ($composer['autoload']['files'] as &$path) {
    $path = $root.'/'.$path;
}
unset($path);
file_put_contents($directory.'/composer.json', json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
passthru('composer install --no-interaction --prefer-dist --working-dir='.escapeshellarg($directory), $status);
if ($status !== 0) {
    exit($status);
}
echo 'Run with CRM_BROWSER_VENDOR='.escapeshellarg($directory.'/vendor')." npm run test:browser\n";
