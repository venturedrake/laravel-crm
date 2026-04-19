<div class="grid gap-5">
    <x-mary-card separator>
        @foreach($this->activities as $activity)
            @php
                $userName = $activity->causeable->name ?? $activity->recordable?->createdByUser?->name ?? null;
                $activityType = $activity->recordable_type ? strtolower(class_basename($activity->recordable_type)) : null;
            @endphp

            <x-crm-timeline-item :title="($userName ? $userName . ' created a ' : 'Created a ') . ($activityType ?? 'activity')" :subtitle="$activity->created_at->format('m/d/Y h:i A')" :activity="$activity" :activityType="$activityType"  :first="$loop->first" :last="$loop->last" />
            
        @endforeach
    </x-mary-card>
</div>