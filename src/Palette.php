<?php

namespace Syriable\FilamentAdvancedComponents;

use Illuminate\Support\Collection;

class Palette
{
    public function processColors(array $colors, ?array $shades = [], ?array $labels = []): array | Collection
    {
        return collect($colors)->mapWithKeys(function ($color, $key) use ($shades, $labels) {
            return [$key => $this->buildColor($key, $color, $shades, $labels)];
        });
    }

    public function buildColor(string $key, array | string $color, array $shades, array $labels): array
    {
        if (is_array($color)) {
            $value = isset($shades[$key]) ? $color[$shades[$key]] : $color[500];
            $shade = $shades[$key] ?? 500;
        } else {
            $value = $color;
            $shade = null;
        }

        $label = $labels[$key] ?? (string) str($key)->title()->replace('-', ' ');
        $type = $this->determineType($value);

        return [
            'key' => $key,
            'property' => '--' . $key . ($shade ? '-' . $shade : ''),
            'label' => $label,
            'type' => $type,
            'value' => $value,
        ];
    }

   
public function determineType(string $value): string
{
    // Remove any whitespace for consistent matching
    $trimmedValue = trim($value);
    
    // Check for hex color (#ffffff or ffffff)
    if (preg_match('/^#?[a-fA-F0-9]{6}$/', $trimmedValue) === 1) {
        return 'hex';
    }
    
    // Check for RGB format (255, 255, 255) or rgb(255, 255, 255)
    elseif (preg_match('/^(?:rgb\()?(\d{1,3}),\s*(\d{1,3}),\s*(\d{1,3})\)?$/', $trimmedValue) === 1) {
        return 'rgb';
    }
    
    // Check for OKLCH format: oklch(L C H) where L is 0-1, C is 0-0.4+, H is 0-360
    elseif (preg_match('/^oklch\(\s*([0-1](?:\.\d+)?)\s+([0-9]*\.?[0-9]+)\s+([0-9]*\.?[0-9]+)\s*\)$/', $trimmedValue) === 1) {
        return 'oklch';
    }

    return 'class';
}
}