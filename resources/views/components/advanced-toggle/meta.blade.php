{{--
    Contextual content under the switch: the state badge, state description,
    and (only while genuinely disabled) the reason why. `$vm` is inherited
    from the including view's scope.
--}}
<div
    class="fi-advanced-toggle-meta"
    style="--fi-advanced-toggle-duration: {{ $vm->animationDurationCss() }}"
>
    @if ($vm->badgeLabel)
        <x-filament::badge
            :color="$vm->badgeColor"
            class="fi-advanced-toggle-badge"
        >
            {{ $vm->badgeLabel }}
        </x-filament::badge>
    @endif

    @if ($vm->stateDescription)
        <p class="fi-advanced-toggle-description">{{ $vm->stateDescription }}</p>
    @endif

    @if ($vm->disabledReason)
        <p class="fi-advanced-toggle-disabled-reason">{{ $vm->disabledReason }}</p>
    @endif
</div>
