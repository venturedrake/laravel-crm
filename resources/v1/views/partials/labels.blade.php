@foreach($labels as $label)
@if((isset($limit) && $loop->iteration <= $limit) || !isset($limit))    
<span class="badge badge-primary" style="background-color: #{{ $label->hex }}">{{ $label->name }}</span>
@endif
@endforeach
@if(isset($limit) && $labels->count() > $limit)
    <span class="badge badge-secondary">+{{ $labels->count() - $limit }}</span>
@endif 