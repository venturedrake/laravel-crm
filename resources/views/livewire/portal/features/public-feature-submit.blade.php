<div>
    <a href="{{ route('laravel-crm.portal.features.index') }}" class="link link-hover text-sm">&larr; Back to features</a>

    <div class="card bg-base-100 shadow mt-4">
        <div class="card-body">
            <h1 class="card-title text-xl mb-2">Submit a feature request</h1>
            <p class="text-sm text-base-content/70 mb-4">
                Share what you'd like to see. Others can vote and comment on your idea.
            </p>

            @if ($errors->any())
                <div class="alert alert-error mb-4">
                    <ul class="text-sm m-0">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('laravel-crm.portal.features.store') }}" class="space-y-3">
                @csrf

                <div>
                    <label class="label" for="feature-title">
                        <span class="label-text">Title</span>
                    </label>
                    <input id="feature-title" name="title" type="text" required autofocus
                           value="{{ old('title') }}"
                           class="input input-bordered w-full" />
                </div>

                <div>
                    <label class="label" for="feature-description">
                        <span class="label-text">Description</span>
                    </label>
                    <textarea id="feature-description" name="description" rows="5"
                              class="textarea textarea-bordered w-full">{{ old('description') }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary w-full mt-4">
                    Submit
                </button>
            </form>
        </div>
    </div>
</div>
