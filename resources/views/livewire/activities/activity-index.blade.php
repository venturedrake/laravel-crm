<div class="grid gap-5">
    <x-mary-card separator>
        @foreach($this->activities as $activity)
            @php
                $userName = $activity->causeable->name ?? $activity->recordable?->createdByUser?->name ?? null;
                $activityType = $activity->recordable_type ? strtolower(class_basename($activity->recordable_type)) : null;
            @endphp

            {{-- An activity type may word its own entry (timelineTitle) and say
                 when it happened rather than when it was entered (timelineDate). --}}
            <x-crm-timeline-item
                :title="method_exists($activity->recordable, 'timelineTitle') ? ucfirst($activity->recordable->timelineTitle($userName)) : (($userName ? $userName . ' created a ' : 'Created a ') . ($activityType ?? 'activity'))"
                :subtitle="(method_exists($activity->recordable, 'timelineDate') ? $activity->recordable->timelineDate() : $activity->created_at)->format('m/d/Y h:i A')"
                :activity="$activity" :activityType="$activityType"  :first="$loop->first" :last="$loop->last" />
            
        @endforeach
    </x-mary-card>
</div>