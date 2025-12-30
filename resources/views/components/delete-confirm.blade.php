@props(['model', 'id', 'name' => null, 'deleting' => null])

<dialog id="modal{{ ($name) ? $name : 'Delete'.ucfirst($model).$id }}" class="modal">
    <div class="modal-box text-left">
        <h3 class="text-lg font-bold">Delete {{ $model }}?</h3>
        <p class="py-4">You're about to delete this {{ $model }}. This action cannot be reversed.</p>
        <div class="modal-action">
            <form method="dialog">
                <!-- if there is a button in form, it will close the modal -->
                <button class="btn">Cancel</button>
                <button wire:click="delete({{ $id }})" class="btn btn-error text-white">Delete {{ $model }}</button>
            </form>
        </div>
    </div>
</dialog>