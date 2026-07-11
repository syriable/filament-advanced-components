{{--
    DiffField's modal body (DiffField::getViewDiffAction()'s modalContent()).

    The heading, icon, close button, and (when onRollback() is registered)
    the "Rollback" submit button are all Filament's own action-modal chrome —
    only the Side-by-side/Inline toggle and the two views below are custom,
    and the toggle itself is a plain client-side Alpine flag, no Livewire
    round-trip.
--}}
@php
    $oldValue = $field->getOldValue();
    $newValue = $field->getNewValue();
    $tokens = $field->getWordDiffTokens();
@endphp

<div x-data="{ view: 'side-by-side' }" class="fi-diff-field-modal">
    <div class="fi-diff-field-modal-toggle" role="tablist">
        <button
            type="button"
            role="tab"
            x-on:click="view = 'side-by-side'"
            x-bind:class="{ 'fi-diff-field-modal-toggle-active': view === 'side-by-side' }"
            class="fi-diff-field-modal-toggle-option"
        >
            {{ __('filament-advanced-components::diff-field.side_by_side') }}
        </button>

        <button
            type="button"
            role="tab"
            x-on:click="view = 'inline'"
            x-bind:class="{ 'fi-diff-field-modal-toggle-active': view === 'inline' }"
            class="fi-diff-field-modal-toggle-option"
        >
            {{ __('filament-advanced-components::diff-field.inline') }}
        </button>
    </div>

    <div x-show="view === 'side-by-side'">
        <div class="fi-diff-field-modal-box fi-diff-field-modal-box-old">
            <span class="fi-diff-field-modal-box-label">{{ __('filament-advanced-components::diff-field.old') }}</span>

            @if (filled($oldValue))
                <p class="fi-diff-field-modal-box-content">{{ $oldValue }}</p>
            @else
                <p class="fi-diff-field-modal-box-empty">{{ __('filament-advanced-components::diff-field.empty_placeholder') }}</p>
            @endif
        </div>

        <div class="fi-diff-field-modal-box fi-diff-field-modal-box-new">
            <span class="fi-diff-field-modal-box-label">{{ __('filament-advanced-components::diff-field.new') }}</span>

            @if (filled($newValue))
                <p class="fi-diff-field-modal-box-content">{{ $newValue }}</p>
            @else
                <p class="fi-diff-field-modal-box-empty">{{ __('filament-advanced-components::diff-field.empty_placeholder') }}</p>
            @endif
        </div>
    </div>

    <div x-show="view === 'inline'" x-cloak>
        <div class="fi-diff-field-modal-box">
            {{-- Word spacing already lives inside each token's own text, so
                 this paragraph doesn't need (and must not use) pre-wrap —
                 the template's own indentation between @foreach iterations
                 would otherwise render as literal line breaks. --}}
            <p class="fi-diff-field-modal-inline-content">@foreach ($tokens as $token){{--
                --}}<span class="fi-diff-field-modal-token fi-diff-field-modal-token-{{ $token->type->value }}">{{ $token->text }}</span>{{--
            --}}@endforeach</p>
        </div>
    </div>
</div>
