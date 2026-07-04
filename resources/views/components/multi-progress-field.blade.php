{{--
    Form-field wrapper around the shared multi-progress bar.

    Unlike table columns and infolist entries — whose wrappers are applied by
    Filament's rendering pipeline — form field views are responsible for
    rendering their own wrapper (label, helper text, hint, validation
    messages). The bar itself lives in the shared `multi-progress` template;
    Blade's @include inherits this view's scope, so the extracted component
    closures ($getProgressData(), $isCompact(), ...) are available to it.
--}}
<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @include('filament-advanced-components::components.multi-progress')
</x-dynamic-component>
