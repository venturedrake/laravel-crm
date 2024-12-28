@if($task->completed_at)
<span class="badge badge-success">Complete</span> 
@else
<span class="badge badge-primary">Pending</span>
@endif    