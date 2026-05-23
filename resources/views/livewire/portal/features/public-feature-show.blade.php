<div>
    <a href="{{ route('laravel-crm.portal.features.index') }}" class="link link-hover text-sm">&larr; {{ ucfirst(__('laravel-crm::lang.back')) }} {{ __('laravel-crm::lang.to') }} {{ __('laravel-crm::lang.features') }}</a>

    <div class="card bg-base-100 shadow mt-4">
        <div class="card-body">
            <div class="flex flex-row gap-4 items-start">
                <div class="flex flex-col items-center min-w-[3.5rem]">
                    @auth
                        @if ($hasVoted)
                            <form method="POST" action="{{ route('laravel-crm.portal.features.unvote', $feature->external_id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-primary btn-sm flex flex-col h-auto py-1 px-3" title="{{ ucfirst(__('laravel-crm::lang.unvote')) }}">
                                    <span class="text-base">▲</span>
                                    <span class="font-semibold">{{ $feature->votes_count }}</span>
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('laravel-crm.portal.features.vote', $feature->external_id) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline btn-sm flex flex-col h-auto py-1 px-3" title="{{ ucfirst(__('laravel-crm::lang.vote')) }}">
                                    <span class="text-base">▲</span>
                                    <span class="font-semibold">{{ $feature->votes_count }}</span>
                                </button>
                            </form>
                        @endif
                    @else
                        <a href="{{ route('laravel-crm.portal.login', ['intended' => route('laravel-crm.portal.features.show', $feature->external_id)]) }}"
                           class="btn btn-outline btn-sm flex flex-col h-auto py-1 px-3" title="{{ ucfirst(__('laravel-crm::lang.login')) }}">
                            <span class="text-base">▲</span>
                            <span class="font-semibold">{{ $feature->votes_count }}</span>
                        </a>
                    @endauth
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-xl font-semibold m-0">{{ $feature->title }}</h1>
                        @if ($feature->status)
                            <span class="badge badge-sm text-white"
                                  style="background-color: {{ $feature->status->color ?? '#6c757d' }}">
                                {{ $feature->status->name }}
                            </span>
                        @endif
                    </div>
                    @if ($feature->description)
                        <p class="text-sm text-base-content/80 mt-2 whitespace-pre-line">{{ $feature->description }}</p>
                    @endif
                    <div class="text-xs text-base-content/60 mt-2">
                        {{ ucfirst(__('laravel-crm::lang.created_by')) }} {{ $feature->submittedBy?->name ?? '-' }}
                        · {{ $feature->created_at?->diffForHumans() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-8">
        <h2 class="text-lg font-semibold mb-3">
            {{ ucfirst(__('laravel-crm::lang.comments')) }} ({{ $feature->comments_count }})
        </h2>

        @if ($errors->any())
            <div class="alert alert-error mb-4">
                <ul class="text-sm m-0">
                    @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <ul class="space-y-3 mb-6">
            @foreach ($comments as $comment)
                <li class="card {{ $comment->is_admin_reply ? 'bg-primary/10 border border-primary/30' : 'bg-base-100' }} shadow-sm">
                    <div class="card-body py-3">
                        <div class="flex items-center gap-2 text-xs">
                            <strong>{{ $comment->createdByUser?->name ?? ucfirst(__('laravel-crm::lang.user')) }}</strong>
                            @if ($comment->is_admin_reply)
                                <span class="badge badge-primary badge-xs">{{ ucfirst(__('laravel-crm::lang.admin')) }}</span>
                            @endif
                            <span class="text-base-content/60">· {{ $comment->created_at?->diffForHumans() }}</span>
                        </div>
                        <p class="text-sm mt-1 whitespace-pre-line">{{ $comment->body }}</p>
                    </div>
                </li>
            @endforeach
        </ul>

        @auth
            <form method="POST" action="{{ route('laravel-crm.portal.features.comments.store', $feature->external_id) }}" class="space-y-2">
                @csrf
                <label class="label" for="comment-body">
                    <span class="label-text">{{ ucfirst(__('laravel-crm::lang.comment')) }}</span>
                </label>
                <textarea id="comment-body" name="body" required rows="3"
                          class="textarea textarea-bordered w-full">{{ old('body') }}</textarea>
                <button type="submit" class="btn btn-primary btn-sm">{{ ucfirst(__('laravel-crm::lang.send')) }}</button>
            </form>
        @else
            <div class="alert">
                <a href="{{ route('laravel-crm.portal.login', ['intended' => route('laravel-crm.portal.features.show', $feature->external_id)]) }}"
                   class="link link-primary">{{ ucfirst(__('laravel-crm::lang.login')) }}</a>
                <span>{{ __('laravel-crm::lang.to') }} {{ __('laravel-crm::lang.comment') }}.</span>
            </div>
        @endauth
    </div>
</div>
