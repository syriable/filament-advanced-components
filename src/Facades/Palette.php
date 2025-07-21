<?php

namespace Syriable\FilamentAdvancedComponents\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Collection processColors(array $colors, null | array $shades)
 * @method static array buildColor(string $key, array | string $color, array $shades)
 * @method static string determineType(string $value)
 *
 * @see \Syriable\FilamentAdvancedComponents\Palette
 */
class Palette extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Syriable\FilamentAdvancedComponents\Palette::class;
    }
}