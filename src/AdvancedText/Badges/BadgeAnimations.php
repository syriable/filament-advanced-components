<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Badges;

use InvalidArgumentException;

/**
 * Registry mapping animation names to CSS classes.
 *
 * `pulse` and `bounce` ship with the package (their keyframes live in
 * `resources/css/advanced-text.css`). Register additional animations from a
 * service provider — the CSS class is yours to define in your theme:
 *
 * ```php
 * BadgeAnimations::register('wiggle', 'my-badge-wiggle');
 *
 * AdvancedBadge::make('New')->animation('wiggle');
 * ```
 */
class BadgeAnimations
{
    /**
     * @var array<string, string>
     */
    protected static array $animations = [
        'pulse' => 'fi-adv-badge-pulse',
        'bounce' => 'fi-adv-badge-bounce',
    ];

    public static function register(string $name, string $cssClass): void
    {
        static::$animations[$name] = $cssClass;
    }

    public static function has(string $name): bool
    {
        return array_key_exists($name, static::$animations);
    }

    public static function resolve(string $name): string
    {
        return static::$animations[$name] ?? throw new InvalidArgumentException(
            "Badge animation [{$name}] is not registered. Register it with " . static::class . '::register().',
        );
    }
}
