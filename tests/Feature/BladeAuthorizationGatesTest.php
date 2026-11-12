<?php

use Illuminate\Support\Facades\Blade;

/**
 * Locks the Blade-level @can gates added by US-007.
 *
 * Server-side authorize() remains authoritative — these assertions guard the UX
 * contract that a read-only user is never shown a control that would 403.
 */
function bladeGatesViewPath(string $relative): string
{
    return dirname(__DIR__, 2).'/resources/views/'.$relative;
}

/**
 * Blade directives that open a block, keyed to the @end… that closes them.
 * Custom module directives (@hasleadsenabled and friends) are matched by shape.
 */
function bladeGatesIsBlockOpener(string $name): bool
{
    return in_array($name, [
        'if', 'can', 'canany', 'cannot', 'unless', 'isset', 'empty',
        'auth', 'guest', 'foreach', 'forelse', 'for', 'while', 'switch', 'error',
    ], true) || (bool) preg_match('/^has\w+enabled$/', $name);
}

/**
 * Walks $source keeping a stack of open Blade blocks and returns, for every
 * occurrence of $control, the stack of enclosing directive texts.
 *
 * This is deliberately stricter than "the gate appears somewhere above": a gate
 * that has already been closed before the control must not count.
 *
 * @return array<int, array<int, string>>
 */
function bladeGatesEnclosingBlocks(string $source, string $control): array
{
    preg_match_all('/@(\w+)/', $source, $matches, PREG_OFFSET_CAPTURE);

    $stack = [];
    $events = [];

    foreach ($matches[1] as $index => [$name, $_]) {
        $position = $matches[0][$index][1];
        $text = $matches[0][$index][0];

        // Capture the balanced parenthesised expression, if any.
        $after = $position + strlen($text);
        if (($source[$after] ?? '') === '(') {
            $depth = 0;
            for ($i = $after; $i < strlen($source); $i++) {
                $depth += ($source[$i] === '(') ? 1 : (($source[$i] === ')') ? -1 : 0);
                if ($depth === 0) {
                    $text = substr($source, $position, $i - $position + 1);

                    break;
                }
            }
        }

        $events[] = ['pos' => $position, 'name' => $name, 'text' => $text];
    }

    $occurrences = [];
    $offset = 0;

    while (($controlPosition = strpos($source, $control, $offset)) !== false) {
        $stack = [];

        foreach ($events as $event) {
            if ($event['pos'] >= $controlPosition) {
                break;
            }

            if (str_starts_with($event['name'], 'end')) {
                $closes = substr($event['name'], 3);

                for ($i = count($stack) - 1; $i >= 0; $i--) {
                    if ($stack[$i]['name'] === $closes) {
                        $stack = array_slice($stack, 0, $i);

                        break;
                    }
                }

                continue;
            }

            if (bladeGatesIsBlockOpener($event['name'])) {
                $stack[] = $event;
            }
        }

        $occurrences[] = array_column($stack, 'text');
        $offset = $controlPosition + 1;
    }

    return $occurrences;
}

/**
 * Every occurrence of $control must sit inside a still-open block whose
 * directive text contains $gate.
 */
function bladeGatesAssertControlIsGated(string $relative, string $gate, string $control): void
{
    $source = file_get_contents(bladeGatesViewPath($relative));

    expect($source)->toContain($gate)
        ->and($source)->toContain($control);

    $occurrences = bladeGatesEnclosingBlocks($source, $control);

    expect($occurrences)->not->toBeEmpty("`{$control}` not found in {$relative}");

    foreach ($occurrences as $index => $enclosing) {
        $gated = false;

        foreach ($enclosing as $directive) {
            if (str_contains($directive, $gate)) {
                $gated = true;

                break;
            }
        }

        expect($gated)->toBeTrue(
            "occurrence #{$index} of `{$control}` in {$relative} is not enclosed by `{$gate}`"
        );
    }
}

dataset('gated_blade_controls', [
    // Invoice show page — send + pay panels.
    'invoice send' => ['livewire/invoices/invoice-show.blade.php', "@can('edit crm invoices')", '<livewire:crm-invoice-send'],
    'invoice pay' => ['livewire/invoices/invoice-show.blade.php', "@can('edit crm invoices')", '<livewire:crm-invoice-pay'],

    // Activity sub-item mutating controls.
    'note edit' => ['livewire/notes/note-item.blade.php', "@can('edit crm notes')", 'wire:click="edit"'],
    'note pin' => ['livewire/notes/note-item.blade.php', "@can('edit crm notes')", 'wire:click="pin"'],
    'note unpin' => ['livewire/notes/note-item.blade.php', "@can('edit crm notes')", 'wire:click="unpin"'],
    'note delete' => ['livewire/notes/note-item.blade.php', "@can('delete crm notes')", 'modalDeleteNoteItem'],
    'task edit' => ['livewire/tasks/task-item.blade.php', "@can('edit crm tasks')", 'wire:click="edit"'],
    'task complete' => ['livewire/tasks/task-item.blade.php', "@can('edit crm tasks')", 'wire:click="complete"'],
    'task delete' => ['livewire/tasks/task-item.blade.php', "@can('delete crm tasks')", 'modalDeleteTaskItem'],
    'call edit' => ['livewire/calls/call-item.blade.php', "@can('edit crm calls')", 'wire:click="edit"'],
    'call delete' => ['livewire/calls/call-item.blade.php', "@can('delete crm calls')", 'modalDeleteCallItem'],
    'meeting edit' => ['livewire/meetings/meeting-item.blade.php', "@can('edit crm meetings')", 'wire:click="edit"'],
    'meeting delete' => ['livewire/meetings/meeting-item.blade.php', "@can('delete crm meetings')", 'modalDeleteMeetingItem'],
    'lunch edit' => ['livewire/lunches/lunch-item.blade.php', "@can('edit crm lunches')", 'wire:click="edit"'],
    'lunch delete' => ['livewire/lunches/lunch-item.blade.php', "@can('delete crm lunches')", 'modalDeleteLunchItem'],
    'file delete' => ['livewire/files/file-item.blade.php', "@can('delete crm files')", 'modalDeleteFileItem'],
    'email edit' => ['livewire/email-messages/email-message-item.blade.php', "@can('edit crm emails')", 'wire:click="edit"'],
    'email delete' => ['livewire/email-messages/email-message-item.blade.php', "@can('delete crm emails')", 'modalDeleteEmailMessageItem'],
    'email log button' => ['livewire/activity-menu.blade.php', "@can('create crm emails')", 'addEmail()'],

    // Sub-item inline edit form save buttons.
    'note save' => ['livewire/notes/note-item.blade.php', "@can('edit crm notes')", 'spinner="update"'],
    'task save' => ['livewire/tasks/task-item.blade.php', "@can('edit crm tasks')", 'spinner="update"'],
    'call save' => ['livewire/calls/call-item.blade.php', "@can('edit crm calls')", 'spinner="update"'],
    'email save' => ['livewire/email-messages/email-message-item.blade.php', "@can('edit crm emails')", 'spinner="update"'],
    'meeting save' => ['livewire/meetings/meeting-item.blade.php', "@can('edit crm meetings')", 'spinner="update"'],
    'lunch save' => ['livewire/lunches/lunch-item.blade.php', "@can('edit crm lunches')", 'spinner="update"'],

    // Related "add" forms.
    'note related save' => ['livewire/notes/note-related.blade.php', "@can('create crm notes')", 'wire:submit="save"'],
    'task related save' => ['livewire/tasks/task-related.blade.php', "@can('create crm tasks')", 'wire:submit="save"'],
    'call related save' => ['livewire/calls/call-related.blade.php', "@can('create crm calls')", 'wire:submit="save"'],
    'email related save' => ['livewire/email-messages/email-message-related.blade.php', "@can('create crm emails')", 'wire:submit="save"'],
    'meeting related save' => ['livewire/meetings/meeting-related.blade.php', "@can('create crm meetings')", 'wire:submit="save"'],
    'lunch related save' => ['livewire/lunches/lunch-related.blade.php', "@can('create crm lunches')", 'wire:submit="save"'],
    'file related upload' => ['livewire/files/file-related.blade.php', "@can('create crm files')", '@click="submit()"'],

    // Line items and related contacts — polymorphic parents, so the policy
    // ability form is used to mirror authorize('update', $this->model).
    'model products add' => ['livewire/model-products.blade.php', '$canManageProducts', 'wire:click="add"'],
    'model products remove' => ['livewire/model-products.blade.php', '$canManageProducts', 'wire:click="remove('],
    'related people remove' => ['livewire/related-people.blade.php', "@can('update', \$model)", 'wire:click="remove('],
    'related people link' => ['livewire/related-people.blade.php', "@can('update', \$model)", 'wire:submit="add"'],
    'related orgs remove' => ['livewire/related-organizations.blade.php', "@can('update', \$model)", 'wire:click="remove('],
    'related orgs link' => ['livewire/related-organizations.blade.php', "@can('update', \$model)", 'wire:submit="add"'],
]);

dataset('kanban_boards', [
    'deals' => ['deals/board.blade.php', 'crm-deal-board', 'edit crm deals'],
    'leads' => ['leads/board.blade.php', 'crm-lead-board', 'edit crm leads'],
    'quotes' => ['quotes/board.blade.php', 'crm-quote-board', 'edit crm quotes'],
    'features' => ['features/board.blade.php', 'crm-feature-board', 'edit crm features'],
]);

it('gates every mutating control behind the matching permission', function (string $view, string $gate, string $control) {
    bladeGatesAssertControlIsGated($view, $gate, $control);
})->with('gated_blade_controls');

it('passes a permission driven sortable flag into every kanban board', function (string $view, string $tag, string $permission) {
    $source = file_get_contents(bladeGatesViewPath($view));

    expect($source)->toContain("<livewire:{$tag} ")
        ->and($source)->toContain(":sortable=\"auth()->user()?->can('{$permission}') ?? false\"");
})->with('kanban_boards');

it('only initialises sortablejs when the sortable flag is true', function (string $board) {
    $source = file_get_contents(bladeGatesViewPath("livewire/{$board}.blade.php"));

    expect($source)->toContain("@includeWhen(\$sortable, 'laravel-crm::livewire.kanban-board.sortable'");
})->with([
    'deals/deal-board',
    'leads/lead-board',
    'quotes/quote-board',
    'features/feature-board',
]);

it('keeps every gated blade compiling with balanced directives', function () {
    $views = [
        'livewire/invoices/invoice-show.blade.php',
        'livewire/notes/note-item.blade.php',
        'livewire/notes/note-related.blade.php',
        'livewire/tasks/task-item.blade.php',
        'livewire/tasks/task-related.blade.php',
        'livewire/calls/call-item.blade.php',
        'livewire/calls/call-related.blade.php',
        'livewire/meetings/meeting-item.blade.php',
        'livewire/meetings/meeting-related.blade.php',
        'livewire/lunches/lunch-item.blade.php',
        'livewire/lunches/lunch-related.blade.php',
        'livewire/files/file-item.blade.php',
        'livewire/files/file-related.blade.php',
        'livewire/model-products.blade.php',
        'livewire/related-people.blade.php',
        'livewire/related-organizations.blade.php',
        'deals/board.blade.php',
        'leads/board.blade.php',
        'quotes/board.blade.php',
        'features/board.blade.php',
    ];

    foreach ($views as $view) {
        $source = file_get_contents(bladeGatesViewPath($view));

        $canany = substr_count($source, '@canany(');
        $endcanany = substr_count($source, '@endcanany');
        $can = substr_count($source, '@can(');
        $endcan = substr_count($source, '@endcan') - $endcanany;

        expect($can)->toBe($endcan, "@can/@endcan imbalance in {$view}")
            ->and($canany)->toBe($endcanany, "@canany/@endcanany imbalance in {$view}")
            ->and(Blade::compileString($source))->toBeString()->not->toBe('');
    }

    expect($views)->toHaveCount(20);
});
