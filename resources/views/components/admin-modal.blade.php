@props([
    'name',
    'title' => '',
    'subtitle' => '',
    'show' => false,
    'wide' => false,
])

<div
    class="pg-modal-root"
    x-data="{ show: @js($show) }"
    x-cloak
    x-show="show"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') show = true"
    x-on:close-modal.window="if ($event.detail === '{{ $name }}' || $event.detail === 'all') show = false"
    x-on:keydown.escape.window="show = false"
    style="display: none;"
>
    <div class="pg-modal-backdrop" x-show="show" x-transition.opacity @click="show = false"></div>

    <div
        class="pg-modal-panel {{ $wide ? 'is-wide' : '' }}"
        role="dialog"
        aria-modal="true"
        x-show="show"
        x-transition
        @click.stop
    >
        @if ($title !== '')
            <header class="pg-modal-header">
                <div>
                    <h2>{{ $title }}</h2>
                    @if ($subtitle !== '')
                        <p>{{ $subtitle }}</p>
                    @endif
                </div>
                <button type="button" class="pg-modal-close" @click="show = false" aria-label="Kapat">&times;</button>
            </header>
        @endif

        <div class="pg-modal-body">
            {{ $slot }}
        </div>
    </div>
</div>
