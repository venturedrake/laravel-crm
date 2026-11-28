{{-- The delete confirmation for an activity (note, task, call, …).
     The Delete button stays disabled until the word "confirm" is typed, so
     an activity cannot be removed by a stray click. The component's delete()
     still authorizes the action on the server.

     $dialogId  id of the <dialog>, opened with {dialogId}.showModal()
     $what      what is being deleted, e.g. "call" --}}
<dialog id="{{ $dialogId }}" class="modal" x-data="{ typed: '' }" x-on:close="typed = ''">
    <div class="modal-box text-left">
        <h3 class="text-lg font-bold">Delete {{ $what }}?</h3>
        <p class="py-4">You're about to delete this {{ $what }}. This action cannot be reversed.</p>
        <label class="text-sm font-semibold" for="{{ $dialogId }}Confirm">{{ ucfirst(__('laravel-crm::lang.type_confirm_to_delete')) }}</label>
        <input
            id="{{ $dialogId }}Confirm"
            type="text"
            class="input w-full mt-1"
            x-model="typed"
            x-on:keydown.enter.prevent
            autocomplete="off"
            autocapitalize="off"
            spellcheck="false" />
        <div class="modal-action">
            <form method="dialog">
                <button class="btn">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</button>
                <button
                    wire:click="delete"
                    class="btn btn-error text-white"
                    x-bind:disabled="typed.trim().toLowerCase() !== 'confirm'"
                >{{ ucfirst(__('laravel-crm::lang.delete')) }}</button>
            </form>
        </div>
    </div>
</dialog>
