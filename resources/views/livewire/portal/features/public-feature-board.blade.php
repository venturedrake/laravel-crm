<div>
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-6">
        <h1 class="text-2xl font-semibold">Feature requests</h1>
        <a href="{{ route('laravel-crm.portal.features.create') }}" class="btn btn-primary btn-sm">
            Submit a feature
        </a>
    </div>

    <div class="flex flex-wrap items-center gap-2 mb-4">
        <select wire:model.live="feature_status_id" class="select select-bordered select-sm">
            <option value="">All statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->id }}">{{ $status->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="sort" class="select select-bordered select-sm">
            <option value="votes">Top voted</option>
            <option value="newest">Newest</option>
        </select>
    </div>

    @if ($features->isEmpty())
        <div class="card bg-base-100 shadow">
            <div class="card-body text-center text-base-content/70">
                No feature requests yet.
            </div>
        </div>
    @else
        <ul class="space-y-3">
            @foreach ($features as $feature)
                <li class="card bg-base-100 shadow">
                    <div class="card-body flex flex-row gap-4 items-start py-4">
                        <div class="flex flex-col items-center min-w-[3.5rem]">
                            @auth
                                @if (($feature->voted_by_user ?? 0) > 0)
                                    <form method="POST" action="{{ route('laravel-crm.portal.features.unvote', $feature->external_id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-primary btn-sm flex flex-col h-auto py-1 px-3" title="Remove vote">
                                            <span class="text-base">▲</span>
                                            <span class="font-semibold">{{ $feature->votes_count }}</span>
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('laravel-crm.portal.features.vote', $feature->external_id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline btn-sm flex flex-col h-auto py-1 px-3" title="Vote">
                                            <span class="text-base">▲</span>
                                            <span class="font-semibold">{{ $feature->votes_count }}</span>
                                        </button>
                                    </form>
                                @endif
                            @else
                                <a href="{{ route('laravel-crm.portal.login', ['intended' => route('laravel-crm.portal.features.show', $feature->external_id)]) }}"
                                   class="btn btn-outline btn-sm flex flex-col h-auto py-1 px-3" title="Login to vote">
                                    <span class="text-base">▲</span>
                                    <span class="font-semibold">{{ $feature->votes_count }}</span>
                                </a>
                            @endauth
                        </div>

                        <div class="flex-1 min-w-0">
                            <a href="{{ route('laravel-crm.portal.features.show', $feature->external_id) }}" class="text-base font-semibold link link-hover">
                                {{ $feature->title }}
                            </a>
                            @if ($feature->status)
                                <span class="badge badge-sm text-white align-middle ml-2"
                                      style="background-color: {{ $feature->status->color ?? '#6c757d' }}">
                                    {{ $feature->status->name }}
                                </span>
                            @endif
                            @if ($feature->description)
                                <p class="text-sm text-base-content/70 mt-1 line-clamp-2">{{ $feature->description }}</p>
                            @endif
                            <div class="text-xs text-base-content/60 mt-1">
                                {{ $feature->comments_count }} comment{{ $feature->comments_count === 1 ? '' : 's' }}
                            </div>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="mt-6">
            {{ $features->withQueryString()->links() }}
        </div>
    @endif
</div>
