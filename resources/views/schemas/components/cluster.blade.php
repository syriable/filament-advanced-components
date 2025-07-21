@php
    use Filament\Forms\Components\Contracts\HasNestedRecursiveValidationRules;
    use Filament\Forms\Components\Field;
    use Filament\Schemas\Components\Component;

    $fieldWrapperView = $getFieldWrapperView();

    $errorMessages = null;
    $errorMessage = null;

    foreach ($getChildComponentContainer()->getComponents() as $childComponent) {
        if (!($childComponent instanceof Field)) {
            continue;
        }

        $statePath = $childComponent->getStatePath();

        if (blank($statePath)) {
            continue;
        }

        if ($errors->has($statePath)) {
            if ($childComponent->shouldShowAllValidationMessages()) {
                $errorMessages = $errors->get($statePath);
                $shouldShowAllValidationMessages = true;
            } else {
                $errorMessage = $errors->first($statePath);
            }

            $areHtmlValidationMessagesAllowed = $childComponent->areHtmlValidationMessagesAllowed();

            break;
        }

        if (!($childComponent instanceof HasNestedRecursiveValidationRules)) {
            continue;
        }

        if ($errors->has("{$statePath}.*")) {
            if ($childComponent->shouldShowAllValidationMessages()) {
                $errorMessages = $errors->get("{$statePath}.*");
                $shouldShowAllValidationMessages = true;
            } else {
                $errorMessage = $errors->first("{$statePath}.*");
            }

            $areHtmlValidationMessagesAllowed = $childComponent->areHtmlValidationMessagesAllowed();

            break;
        }
    }
    foreach ($getChildSchema()->getComponents() as $component) {
        if ($component instanceof Component && !empty($component->getDefaultChildComponents())) {
            foreach ($component->getDefaultChildComponents() as $child) {
                $input = "form.{$child->getName()}";
                break;
            }
        } elseif ($component instanceof Field) {
            $input = "form.{$component->getName()}";
            break;
        }
    }
@endphp
<x-dynamic-component :component="$fieldWrapperView" :error-message="$errorMessage" :error-messages="$errorMessages" :are-html-error-messages-allowed="$areHtmlValidationMessagesAllowed ?? false" :should-show-all-validation-messages="$shouldShowAllValidationMessages ?? false"
    :field="$schemaComponent">
    <div x-init="$el.parentElement.previousElementSibling.addEventListener('click', () => {
        const input = document.querySelector('label[for=\'{{ $input }}\']');
        if (input) {
            input.click();
        }
    })"
        {{ $attributes->merge(
                [
                    'id' => $getId(),
                ],
                escape: false,
            )->merge($getExtraAttributes(), escape: false)->class(['w-full']) }}>
        {{ $getChildSchema() }}
    </div>
</x-dynamic-component>
