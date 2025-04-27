<span> 
    <x-mary-button wire:click.prevent="addNote()" tooltip-bottom="{{ ucfirst(__('laravel-crm::lang.add_note')) }}"  icon="far.sticky-note" class="btn-sm btn-square btn-outline" />
    <x-mary-button wire:click.prevent="addTask()" tooltip-bottom="{{ ucfirst(__('laravel-crm::lang.add_task')) }}"  icon="fas.tasks" class="btn-sm btn-square btn-outline" />
    <x-mary-button wire:click.prevent="addCall()" tooltip-bottom="{{ ucfirst(__('laravel-crm::lang.schedule_call')) }}"  icon="fas.phone" class="btn-sm btn-square btn-outline" />
    <x-mary-button tooltip-bottom="{{ ucfirst(__('laravel-crm::lang.send_email')) }}"  icon="far.envelope" class="btn-sm btn-square btn-outline" />
    <x-mary-button wire:click.prevent="addMeeting()" tooltip-bottom="{{ ucfirst(__('laravel-crm::lang.schedule_meeting')) }}"  icon="fas.users" class="btn-sm btn-square btn-outline" />
    <x-mary-button tooltip-bottom="{{ ucfirst(__('laravel-crm::lang.set_deadline')) }}"  icon="far.flag" class="btn-sm btn-square btn-outline" />
    <x-mary-button wire:click.prevent="addLunch()" tooltip-bottom="{{ ucfirst(__('laravel-crm::lang.schedule_lunch')) }}"  icon="forkawesome.cutlery" class="btn-sm btn-square btn-outline" />
    <x-mary-button wire:click.prevent="addFile()" tooltip-bottom="{{ ucfirst(__('laravel-crm::lang.add_file')) }}"  icon="fas.paperclip" class="btn-sm btn-square btn-outline" />
</span>
