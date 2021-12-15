<div>
    @can('view crm people')
        <h6 class="text-uppercase mt-4 section-h6-title"><span>{{ ucfirst(__('laravel-crm::lang.people')) }} ({{ $people->count() }})</span>@can('create crm people')<span class="float-right"><a href="#" data-toggle="modal" data-target="#linkPersonModal" class="btn btn-outline-secondary btn-sm"><span class="fa fa-plus" aria-hidden="true"></span></a></span>@endcan</h6>
        <hr />
        @foreach($people as $person)
            <p><span class="fa fa-user mr-1" aria-hidden="true"></span> <a href="{{ route('laravel-crm.people.show',$person) }}">{{ $person->name }}</a> <span class="float-right"><button wire:click.prevent="remove({{ $person->id }})" type="button" class="btn btn-outline-danger btn-sm"><span class="fa fa-remove"></span></button></span></p>
        @endforeach
    
        <!-- Modal -->
        <div wire:ignore.self class="modal fade" id="linkPersonModal" tabindex="-1" aria-labelledby="linkPersonModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="linkPersonModalLabel">{{ ucfirst(__('laravel-crm::lang.link_a_person')) }} </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form wire:submit.prevent="link">
                        <div class="modal-body autocomplete">
                             @include('laravel-crm::partials.form.hidden',[
                                  'name' => 'person_id',
                                  'value' => null,'attributes' => [
                                    'wire:model' => 'person_id'
                                  ]      
                             ])
                            <script type="text/javascript">
                                let people =  {!! \VentureDrake\LaravelCrm\Http\Helpers\AutoComplete\people() !!}
                            </script>
                            <div class="form-group @error('person_name') text-danger @enderror">
                                <label>{{ ucfirst(__('laravel-crm::lang.person_name')) }}</label>
                                <input wire:model.debounce.10000ms="person_name" type="text" class="form-control" name="person_name"  autocomplete="{{ \Illuminate\Support\Str::random() }}">
                                @error('person_name') <span class="text-danger invalid-feedback-custom">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</button>
                            <button wire:click.prevent="link()" type="button" class="btn btn-primary">{{ ucwords(__('laravel-crm::lang.link_person')) }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
    @push('livewire-js')
        <script>
            $(document).ready(function () {
                bindAutoComplete();

                window.addEventListener('updatedNameFieldAutocomplete', event => {
                    bindAutoComplete();
                });
                
                window.addEventListener('linkedPerson', event => {
                    $('#linkPersonModal').modal('hide');
                });

                function bindAutoComplete(){
                    $('input[name="person_name"]').autocomplete({
                        source: people,
                        onSelectItem: function (item, element) {
                        @this.set('person_id',item.value);
                        @this.set('person_name',item.label);
                        },
                        highlightClass: 'text-danger',
                        treshold: 2,
                    });
                }
            });
        </script>
    @endpush
</div>
