<div class="grid gap-5">
    <x-mary-card separator>
        @foreach($this->activities as $activity)
            @switch($activity->recordable_type)
                @case('VentureDrake\LaravelCrm\Models\Note')
                    <x-mary-timeline-item :title="($activity->causeable->name ?? $activity->recordable->createdByUser->name ?? null). ' created a note'" :subtitle="$activity->created_at->format('m/d/Y h:i A')" />
                    @break
                @case('VentureDrake\LaravelCrm\Models\Task')
                    <x-mary-timeline-item :title="($activity->causeable->name ?? $activity->recordable->createdByUser->name ?? null). ' created a task'" :subtitle="$activity->created_at->format('m/d/Y h:i A')" />
                    @break
                @case('VentureDrake\LaravelCrm\Models\File')
                    <x-mary-timeline-item :title="($activity->causeable->name ?? $activity->recordable->createdByUser->name ?? null). ' uploaded a file'" :subtitle="$activity->created_at->format('m/d/Y h:i A')" />
                    @break
                @case('VentureDrake\LaravelCrm\Models\Call')
                    <x-mary-timeline-item :title="($activity->causeable->name ?? $activity->recordable->createdByUser->name ?? null). ' scheduled a call'" :subtitle="$activity->created_at->format('m/d/Y h:i A')" />
                    @break
                @case('VentureDrake\LaravelCrm\Models\Meeting')
                    <x-mary-timeline-item :title="($activity->causeable->name ?? $activity->recordable->createdByUser->name ?? null). ' scheduled a meeting'" :subtitle="$activity->created_at->format('m/d/Y h:i A')" />
                    @break
                @case('VentureDrake\LaravelCrm\Models\Lunch')
                    <x-mary-timeline-item :title="($activity->causeable->name ?? $activity->recordable->createdByUser->name ?? null). ' scheduled a lunch meeting'" :subtitle="$activity->created_at->format('m/d/Y h:i A')" />
                    @break
            @endswitch
            
        @endforeach
    </x-mary-card>
</div>