@switch(class_basename($note->noteable->getMorphClass()))
    @case('Lead')
        <small>{{ ucfirst(__('laravel-crm::lang.lead')) }}: <a href="{{ route('laravel-crm.leads.show', $note->noteable) }}">{{ $note->noteable->title }}</a></small>
        @break
    @case('Deal')
        <small>{{ ucfirst(__('laravel-crm::lang.deal')) }}: <a href="{{ route('laravel-crm.deals.show', $note->noteable) }}">{{ $note->noteable->title }}</a></small>
        @break
    @case('Quote')
        <small>{{ ucfirst(__('laravel-crm::lang.quote')) }}: <a href="{{ route('laravel-crm.quotes.show', $note->noteable) }}">{{ $note->noteable->title }}</a></small>
        @break
    @case('Order')
        <small>{{ ucfirst(__('laravel-crm::lang.order')) }}: <a href="{{ route('laravel-crm.orders.show', $note->noteable) }}">{{ $note->noteable->title }}</a></small>
        @break
    @case('Person')
        <small>{{ ucfirst(__('laravel-crm::lang.person')) }}: <a href="{{ route('laravel-crm.people.show', $note->noteable) }}">{{ $note->noteable->name }}</a></small>
        @break
    @case('Organisation')
        <small>{{ ucfirst(__('laravel-crm::lang.organisation')) }}: <a href="{{ route('laravel-crm.organisations.show', $note->noteable) }}">{{ $note->noteable->name }}</a></small>
        @break
@endswitch