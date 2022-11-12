@switch(class_basename($task->taskable->getMorphClass()))
    @case('Lead')
        <small>{{ ucfirst(__('laravel-crm::lang.lead')) }}: <a href="{{ route('laravel-crm.leads.show', $task->taskable) }}">{{ $task->taskable->title }}</a></small>
        @break
    @case('Deal')
        <small>{{ ucfirst(__('laravel-crm::lang.deal')) }}: <a href="{{ route('laravel-crm.deals.show', $task->taskable) }}">{{ $task->taskable->title }}</a></small>
        @break
    @case('Quote')
        <small>{{ ucfirst(__('laravel-crm::lang.quote')) }}: <a href="{{ route('laravel-crm.quotes.show', $task->taskable) }}">{{ $task->taskable->title }}</a></small>
        @break
    @case('Order')
        <small>{{ ucfirst(__('laravel-crm::lang.order')) }}: <a href="{{ route('laravel-crm.orders.show', $task->taskable) }}">{{ $task->taskable->title }}</a></small>
        @break
    @case('Person')
        <small>{{ ucfirst(__('laravel-crm::lang.person')) }}: <a href="{{ route('laravel-crm.people.show', $task->taskable) }}">{{ $task->taskable->name }}</a></small>
        @break
    @case('Organisation')
        <small>{{ ucfirst(__('laravel-crm::lang.organisation')) }}: <a href="{{ route('laravel-crm.organisations.show', $task->taskable) }}">{{ $task->taskable->name }}</a></small>
        @break
@endswitch