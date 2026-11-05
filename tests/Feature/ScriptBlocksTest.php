<?php

use Symfony\Component\Finder\Finder;

/*
 * Livewire 3 and 4.0-4.1 hand an @script body to Alpine, which only treats it as
 * statements if the text starts with let, const or if. Anything else becomes
 * `result = <body>`, so a leading comment puts a `const` in expression position and the
 * whole script is a SyntaxError. A trailing `//` comment swallows the closing brace of
 * the wrapper on every version. The PHP suite runs none of this JS, so it can only be
 * caught here: on 4.1.1 and later the same body works, which is why it went unnoticed.
 */

function scriptBlocks(): array
{
    $blocks = [];

    $files = Finder::create()->files()->name('*.blade.php')->in(__DIR__.'/../../resources/views');

    foreach ($files as $file) {
        preg_match_all('/@script\s*<script[^>]*>(.*?)<\/script>\s*@endscript/s', $file->getContents(), $matches);

        foreach ($matches[1] as $body) {
            $blocks[] = [$file->getRelativePathname(), trim($body)];
        }
    }

    return $blocks;
}

it('finds the script blocks it is meant to check', function () {
    expect(collect(scriptBlocks())->pluck(0)->all())
        ->toContain('livewire/kanban-board/scripts.blade.php', 'livewire/kanban-board/sortable.blade.php');
});

it('keeps comments away from the edges of every script block', function () {
    foreach (scriptBlocks() as [$file, $body]) {
        expect(preg_match('#^(//|/\*)#', $body))->toBe(0, "{$file} opens its @script body with a comment")
            ->and(preg_match('#(^|\n)\s*//[^\n]*$#', $body))->toBe(0, "{$file} closes its @script body with a comment");
    }
});
