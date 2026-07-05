{{--
    The switch itself. `$attributes`, `$vm`, and `$entangleExpression` are
    inherited from the including view's scope.

    When confirmation can intercept this click, a thin wrapper captures the
    click *before* it reaches the native `<x-filament::toggle>` button — see
    the class docblock on `ConfirmationManager` for why this, rather than
    forking the button's own markup, is how the "never flip until confirmed"
    guarantee is implemented. `stopPropagation()` during the capture phase
    means the button's own (bubble-phase) click handler — the one that flips
    its entangled state — never runs at all; nothing is set and then
    reverted. When a direction doesn't need confirmation, the event is left
    alone and falls through to the native button exactly as if this wrapper
    didn't exist.
--}}
@if ($vm->interceptsClicks())
    <div
        x-data="{
            __toggleState: {{ $entangleExpression }},
            __togglePending: false,
        }"
        x-on:click.capture="
            if (__togglePending) { $event.stopPropagation(); return; }

            const next = ! __toggleState;
            const needsConfirmation = next
                ? @js($vm->requiresConfirmationWhenTurningOn)
                : @js($vm->requiresConfirmationWhenTurningOff);

            if (! needsConfirmation) return;

            $event.stopPropagation();
            __togglePending = true;

            Promise.resolve(
                $wire.mountAction(
                    @js($vm->confirmationActionName),
                    { state: next },
                    { schemaComponent: @js($vm->mountKey) },
                ),
            ).finally(() => { __togglePending = false });
        "
        x-bind:class="{ 'fi-advanced-toggle-pending': __togglePending }"
        x-bind:aria-busy="__togglePending ? 'true' : 'false'"
        class="fi-advanced-toggle-ctn"
        style="--fi-advanced-toggle-duration: {{ $vm->animationDurationCss() }}"
    >
        <x-filament::toggle
            :attributes="\Filament\Support\prepare_inherited_attributes($attributes)"
        />
    </div>
@else
    <x-filament::toggle
        :attributes="\Filament\Support\prepare_inherited_attributes($attributes)"
    />
@endif
