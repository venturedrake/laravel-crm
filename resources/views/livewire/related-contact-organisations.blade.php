<div>
    @can('view crm contacts')
        <h6 class="text-uppercase mt-4 section-h6-title"><span>{{ ucfirst(__('laravel-crm::lang.organizations')) }} ({{ $contacts->count() }})</span>@can('create crm contacts')<span class="float-right"><a href="#" data-toggle="modal" data-target="#linkOrganisationModal" class="btn btn-outline-secondary btn-sm"><span class="fa fa-plus" aria-hidden="true"></span></a></span>@endcan</h6>
        <hr />
        @foreach($contacts as $contact)
            <p><span class="fa fa-building mr-1" aria-hidden="true"></span> <a href="{{ route('laravel-crm.organisations.show',$contact->entityable) }}">{{ $contact->entityable->name }}</a> <span class="float-right"><button wire:click.prevent="remove({{ $contact->entityable->id }})" type="button" class="btn btn-outline-danger btn-sm"><span class="fa fa-remove"></span></button></span></p>
        @endforeach

        <!-- Modal -->
        <div wire:ignore.self class="modal fade" id="linkOrganisationModal" tabindex="-1" aria-labelledby="linkOrganisationModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="linkOrganisationModalLabel">{{ ucfirst(__('laravel-crm::lang.link_an_organization')) }} </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form wire:submit.prevent="link">
                        <div class="modal-body autocomplete">
                            @include('laravel-crm::partials.form.hidden',[
                            'name' => 'organisation_id',
                            'value' => null,
                           ])
                            <script type="text/javascript">
                                let organisations = {!! \VentureDrake\LaravelCrm\Http\Helpers\AutoComplete\organisations() !!}
                            </script>
                            <div class="form-group @error('organisation_name') text-danger @enderror">
                                <label>{{ ucfirst(__('laravel-crm::lang.organization_name')) }}</label>
                                <input wire:model.debounce.10000ms="organisation_name" type="text" class="form-control" name="organisation_name" autocomplete="{{ \Illuminate\Support\Str::random() }}">
                                @error('organisation_name') <span class="text-danger invalid-feedback-custom">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</button>
                            <button  wire:click.prevent="link()" type="button" class="btn btn-primary">{{ ucwords(__('laravel-crm::lang.link_organization')) }}</button>
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
                
                window.addEventListener('linkedOrganisation', event => {
                    $('#linkOrganisationModal').modal('hide');
                });

                function bindAutoComplete(){
                    $('input[name="organisation_name"]').autocomplete({
                        source: organisations,
                        onSelectItem: function (item, element) {
                        @this.set('organisation_id',item.value);
                        @this.set('organisation_name',item.label);
                        },
                        highlightClass: 'text-danger',
                        treshold: 2,
                    });
                }
            });
        </script>
    @endpush
</div>
