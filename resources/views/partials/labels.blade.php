@foreach($labels as $label)
<span class="badge badge-primary" style="background-color: #{{ $label->hex }}">{{ $label->name }}</span> 
@endforeach    