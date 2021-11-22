<div>
    @can('view crm people')
        <h6 class="text-uppercase mt-4 section-h6-title"><span>{{ ucfirst(__('laravel-crm::lang.people')) }} ({{ $people->count() }})</span>@can('create crm people')<span class="float-right"><a href="#" data-toggle="modal" data-target="#linkPersonModal" class="btn btn-outline-secondary btn-sm"><span class="fa fa-plus" aria-hidden="true"></span></a></span>@endcan</h6>
        <hr />
        @foreach($people as $person)
            <p><span class="fa fa-user" aria-hidden="true"></span> {{ $person->name }}</p>
        @endforeach
    
        <!-- Modal -->
        <div class="modal fade" id="linkPersonModal" tabindex="-1" aria-labelledby="linkPersonModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="linkPersonModalLabel">{{ ucfirst(__('laravel-crm::lang.link_a_person')) }} </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form wire:submit.prevent="link">
                        <div class="modal-body">
                            TEST
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">{{ ucfirst(__('laravel-crm::lang.cancel')) }}</button>
                            <button type="submit" class="btn btn-primary">{{ ucwords(__('laravel-crm::lang.link_person')) }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
</div>
