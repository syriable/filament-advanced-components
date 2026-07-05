<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedToggle\Concerns;

use Closure;
use Filament\Actions\Action;
use Filament\Schemas\Components\Component;

/**
 * Fully custom confirmation modal content — any Filament schema components,
 * not just the title/description pair:
 *
 * ```php
 * AdvancedToggle::make('enabled')
 *     ->confirmationSchema([
 *         TextInput::make('password')->password()->required(),
 *         Textarea::make('reason'),
 *     ]);
 * ```
 *
 * The array (or closure) is handed to the underlying confirmation
 * {@see Action::schema()} untouched, so it is evaluated by
 * Filament itself at modal-build time with the usual injections (`$get`,
 * `$set`, `$record`, ...) — component definitions never run early, and
 * validation is enforced automatically before the confirmation callback ever
 * runs, exactly like any other action modal form.
 */
trait HasConfirmationForm
{
    /**
     * @var array<Component> | Closure | null
     */
    protected array | Closure | null $confirmationSchema = null;

    /**
     * @param  array<Component> | Closure | null  $schema
     */
    public function confirmationSchema(array | Closure | null $schema): static
    {
        $this->confirmationSchema = $schema;

        return $this;
    }

    /**
     * An alias for {@see confirmationSchema()}, for parity with Filament's
     * own deprecated-but-familiar `form()` naming.
     *
     * @param  array<Component> | Closure | null  $schema
     */
    public function confirmationForm(array | Closure | null $schema): static
    {
        return $this->confirmationSchema($schema);
    }

    /**
     * @return array<Component> | Closure | null
     */
    public function getConfirmationSchema(): array | Closure | null
    {
        return $this->confirmationSchema;
    }

    public function hasConfirmationSchema(): bool
    {
        return filled($this->confirmationSchema);
    }
}
