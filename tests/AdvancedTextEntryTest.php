<?php

declare(strict_types=1);

use Filament\Support\Icons\Heroicon;
use Syriable\Filament\Plugins\AdvancedComponents\Infolists\Components\AdvancedTextEntry;

it('renders exactly like a native text entry when no advanced feature is used', function () {
    $html = renderEntry(AdvancedTextEntry::make('email')->state('jane@example.com'));

    expect($html)->toContain('jane@example.com')
        ->and($html)->toContain('fi-in-text')
        ->and($html)->not->toContain('fi-adv-text');
});

it('keeps the entry wrapper (label) outside the advanced decorations', function () {
    $html = renderEntry(AdvancedTextEntry::make('email')->state('jane@example.com')->bold());

    // The typography wrapper must sit inside the entry content, not
    // around the label.
    expect(strpos($html, 'fi-in-entry-label'))->toBeLessThan(strpos($html, 'fi-adv-text-bold'))
        ->and(strpos($html, 'fi-adv-text-bold'))->toBeLessThan(strpos($html, 'jane@example.com'));
});

it('masks the state with the shared masking pipeline', function () {
    $html = renderEntry(
        AdvancedTextEntry::make('phone')
            ->state('+49123456789')
            ->masked()
            ->maskIndex(3)
            ->maskCharacter('*'),
    );

    expect($html)->toContain('+49*********')
        ->and($html)->not->toContain('+49123456789');
});

it('masks emails while keeping them recognizable', function () {
    $html = renderEntry(
        AdvancedTextEntry::make('email')
            ->state('jane.doe@example.com')
            ->maskEmail(),
    );

    expect($html)->toContain('j•••••••@example.com')
        ->and($html)->not->toContain('jane.doe@example.com');
});

it('copies the masked state instead of leaking the raw value', function () {
    $html = renderEntry(
        AdvancedTextEntry::make('secret')
            ->state('hunter2')
            ->masked()
            ->copyable(),
    );

    expect($html)->toContain('navigator.clipboard.writeText')
        ->and($html)->not->toContain('hunter2');
});

it('generates contact links from the state', function () {
    expect(renderEntry(AdvancedTextEntry::make('email')->state('jane@example.com')->mailable()))
        ->toContain('href="mailto:jane@example.com"');

    expect(renderEntry(AdvancedTextEntry::make('phone')->state('+49 (123) 456-789')->callable()))
        ->toContain('href="tel:+49123456789"');

    expect(renderEntry(AdvancedTextEntry::make('phone')->state('+49 123')->whatsappable(message: 'Hi!')))
        ->toContain('href="https://wa.me/49123?text=Hi%21"');
});

it('never generates a link that would leak a masked state', function () {
    $html = renderEntry(
        AdvancedTextEntry::make('email')
            ->state('jane@example.com')
            ->mailable()
            ->masked(),
    );

    expect($html)->not->toContain('mailto:')
        ->and($html)->not->toContain('jane@example.com');
});

it('renders affix images and icons around the content', function () {
    $html = renderEntry(
        AdvancedTextEntry::make('name')
            ->state('Jane')
            ->prefixImage('https://example.com/avatar.png')
            ->imageCircular()
            ->imageSize(24)
            ->suffixIcon(Heroicon::CheckBadge, color: 'success'),
    );

    expect($html)->toContain('src="https://example.com/avatar.png"')
        ->and($html)->toContain('width: 24px; height: 24px;')
        ->and($html)->toContain('border-radius: 50%;')
        ->and($html)->toContain('fi-icon')
        ->and($html)->toContain('fi-adv-text-affixed')
        ->and(strpos($html, '<img'))->toBeLessThan(strpos($html, 'Jane'));
});

it('applies typography classes conditionally', function () {
    $html = renderEntry(
        AdvancedTextEntry::make('name')
            ->state('Jane')
            ->bold()
            ->italic(fn (): bool => true)
            ->strikethrough(fn (): bool => false),
    );

    expect($html)->toContain('fi-adv-text-bold')
        ->and($html)->toContain('fi-adv-text-italic')
        ->and($html)->not->toContain('fi-adv-text-strike');
});

it('shows the character count against a limit', function () {
    $html = renderEntry(
        AdvancedTextEntry::make('bio')
            ->state('Hello world')
            ->characterLimitIndicator(5),
    );

    expect($html)->toContain('11 / 5')
        ->and($html)->toContain('fi-adv-text-character-count-exceeded');
});

it('shows the full state as a tooltip next to limit()', function () {
    $html = renderEntry(
        AdvancedTextEntry::make('bio')
            ->state('Hello wonderful world')
            ->limit(5)
            ->fullStateTooltip(),
    );

    expect($html)->toContain('x-tooltip')
        ->and($html)->toContain('Hello wonderful world');
});

it('supports html decorators', function () {
    $html = renderEntry(
        AdvancedTextEntry::make('name')
            ->state('Jane')
            ->decorateHtmlUsing(fn (string $html): string => "<div class=\"wrapped\">{$html}</div>"),
    );

    // The decorator wraps the content, inside the entry wrapper.
    expect($html)->toContain('<div class="wrapped">')
        ->and(strpos($html, 'fi-in-entry'))->toBeLessThan(strpos($html, '<div class="wrapped">'));
});

it('escapes HTML by default and renders sanitized HTML when enabled', function () {
    expect(renderEntry(AdvancedTextEntry::make('bio')->state('<script>alert(1)</script>')))
        ->not->toContain('<script>');

    $html = renderEntry(
        AdvancedTextEntry::make('bio')
            ->state('<strong>Hi</strong><script>alert(1)</script>')
            ->html(),
    );

    expect($html)->toContain('<strong>Hi</strong>')
        ->and($html)->not->toContain('<script>');
});

it('keeps native badges working', function () {
    $html = renderEntry(
        AdvancedTextEntry::make('tags')
            ->state('php,laravel')
            ->separator(',')
            ->badge(),
    );

    expect(substr_count($html, 'fi-badge'))->toBe(2);
});

it('keeps placeholders working for null state', function () {
    $html = renderEntry(AdvancedTextEntry::make('missing')->placeholder('No value'));

    expect($html)->toContain('No value');
});
