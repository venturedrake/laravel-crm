<div>
    @can('view crm contacts')
        <h6 class="text-uppercase mt-4 section-h6-title"><span>{{ ucfirst(__('laravel-crm::lang.organizations')) }} ({{ $contacts->count() }})</span>@can('create crm contacts') @if($actions)<span class="float-right"><a href="#" data-toggle="modal" data-target="#linkOrganizationModal" class="btn btn-outline-secondary btn-sm"><span class="fa fa-plus" aria-hidden="true"></span></a></span>@endif @endcan</h6>
        <hr />
        @foreach($contacts as $contact)
            <p><span class="fa fa-building mr-1" aria-hidden="true"></span> <a href="{{ route('laravel-crm.organizations.show',$contact->entityable) }}">{{ $contact->entityable->name }}</a> @if($actions)<span class="float-right"><button wire:click.prevent="remove({{ $contact->entityable->id }})" type="button" class="btn btn-outline-danger btn-sm"><span class="fa fa-remove"></span></button></span>@endif</p>
        @endforeach

        <!-- Modal -->
        <div wire:ignore.self class="modal fade" id="linkOrganizationModal" tabindex="-1" aria-labelledby="linkOrganizationModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="linkOrganizationModalLabel">{{ ucfirst(__('laravel-crm::lang.link_an_organization')) }} </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form wire:submit.prevent="link">
                        <div class="modal-body autocomplete">
                            @include('laravel-crm::partials.form.hidden',[
                            'name' => 'organization_id',
                            'value' => null,
                           ])
                            <script type="text/javascript">
                                let organizations = {!! \VentureDrake\LaravelCrm\Http\Helpers\AutoComplete\organizations() !!}
                            </script>
                            <div class="form-group @error('organization_name') text-danger @enderror">
                                <label>{{ ucfirst(__('laravel-crm::lang.organization_name')) }}</label>
                                <input wire:model.debounce.10000ms="organization_name" type="text" class="form-control" name="organization_name" autocomplete="{{ \Illuminate\Support\Str::random() }}">
                                @error('organization_name') <span class="text-danger invalid-feedback-custom">{{ $message }}</span>@enderror
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
                
                window.addEventListener('linkedOrganization', event => {
                    $('#linkOrganizationModal').modal('hide');
                });

                function bindAutoComplete(){
                    $('input[name="organization_name"]').autocomplete({
                        source: organizations,
                        onSelectItem: function (item, element) {
                        @this.set('organization_id',item.value);
                        @this.set('organization_name',item.label);
                        },
                        highlightClass: 'text-danger',
                        treshold: 2,
                    });
                }
            });
        </script>
    @endpush
</div>
