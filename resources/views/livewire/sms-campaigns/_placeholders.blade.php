@props(['placeholders' => []])
<fieldset class="fieldset py-0">
    <legend class="fieldset-legend mb-0.5">{{ ucfirst(__('laravel-crm::lang.available_placeholders')) }}</legend>
    <div class="card bg-base-100 border border-base-300">
        <div class="card-body p-3">
            <div class="text-xs text-base-content/60 mb-2">
                {{ __('laravel-crm::lang.placeholders_hint') }}
            </div>
            <ul class="space-y-1">
                @foreach($placeholders as $key => $description)
                    <li x-data="{ copied: false, token: '{' + @js($key) + '}' }">
                        <button type="button"
                                class="btn btn-ghost btn-xs justify-start w-full font-mono text-xs normal-case"
                                x-on:click="
                                    const done = () => { copied = true; setTimeout(() => copied = false, 1500); };
                                    if (navigator.clipboard && window.isSecureContext) {
                                        navigator.clipboard.writeText(token).then(done).catch(() => {
                                            const ta = document.createElement('textarea');
                                            ta.value = token; ta.style.position = 'fixed'; ta.style.opacity = 0;
                                            document.body.appendChild(ta); ta.select();
                                            try { document.execCommand('copy'); done(); } catch (e) {}
                                            document.body.removeChild(ta);
                                        });
                                    } else {
                                        const ta = document.createElement('textarea');
                                        ta.value = token; ta.style.position = 'fixed'; ta.style.opacity = 0;
                                        document.body.appendChild(ta); ta.select();
                                        try { document.execCommand('copy'); done(); } catch (e) {}
                                        document.body.removeChild(ta);
                                    }
                                "
                                title="{{ $description }}">
                            <span x-show="!copied" x-text="token"></span>
                            <span x-show="copied" x-cloak class="text-success"><span x-text="token"></span> — {{ __('laravel-crm::lang.placeholder_copied') }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</fieldset>
