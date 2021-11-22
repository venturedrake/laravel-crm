<div>
    @can('view crm contacts')
        <h6 class="text-uppercase mt-4 section-h6-title"><span>{{ ucfirst(__('laravel-crm::lang.related_organisations')) }} ({{ $contacts->count() }})</span>@can('create crm contacts')<span class="float-right"><a href="#" data-toggle="modal" data-target="#linkOrganisationModal" class="btn btn-outline-secondary btn-sm"><span class="fa fa-plus" aria-hidden="true"></span></a></span>@endcan</h6>
        <hr />
        @foreach($contacts as $contact)
            <p><span class="fa fa-user" aria-hidden="true"></span> {{ $contact->name }}</p>
        @endforeach

        <!-- Modal -->
        <div class="modal fade" id="linkOrganisationModal" tabindex="-1" aria-labelledby="linkOrganisationModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="linkOrganisationModalLabel">{{ ucfirst(__('laravel-crm::lang.link_an_organisation')) }} </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form wire:submit.prevent="link">
                        <div class="modal-body">
                            INPUT
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</button>
                            <button type="submit" class="btn btn-primary">{{ ucwords(__('laravel-crm::lang.link_organisation')) }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
</div>
