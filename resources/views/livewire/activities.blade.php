<div>
    {{--<h6 class="text-uppercase mt-0">{{ ucfirst(__('laravel-crm::lang.planned')) }}</h6>
    <hr />

    <h6 class="text-uppercase mt-4">{{ ucfirst(__('laravel-crm::lang.complete')) }}</h6>
    <hr />--}}
    @foreach($activities as $activity)
        @switch($activity->recordable_type)
            @case('VentureDrake\LaravelCrm\Models\Note')
                @livewire('note',[
                    'note' => $activity->recordable
                ], key('note_'.$activity->recordable->id))
                @break
            @case('VentureDrake\LaravelCrm\Models\Task')
                @livewire('task',[
                    'task' => $activity->recordable
                ], key('task_'.$activity->recordable->id))
            @break
            @case('VentureDrake\LaravelCrm\Models\File')
                @livewire('file',[
                    'file' => $activity->recordable
                ], key('file_'.$activity->recordable->id))
                @break
            @case('VentureDrake\LaravelCrm\Models\Call')
                @livewire('call',[
                    'call' => $activity->recordable
                ], key('file_'.$activity->recordable->id))
                @break
            @case('VentureDrake\LaravelCrm\Models\Meeting')
                @livewire('meeting',[
                    'meeting' => $activity->recordable
                ], key('file_'.$activity->recordable->id))
                @break
            @case('VentureDrake\LaravelCrm\Models\Lunch')
                @livewire('lunch',[
                    'lunch' => $activity->recordable
                ], key('file_'.$activity->recordable->id))
                @break
        @endswitch
    @endforeach
</div>
