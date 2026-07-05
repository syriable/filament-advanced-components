<?php

declare(strict_types=1);

use Filament\Schemas\Schema;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\HtmlString;
use Syriable\Filament\Plugins\AdvancedComponents\Schemas\Components\Separator;
use Syriable\Filament\Plugins\AdvancedComponents\Separator\Contracts\RendersSeparator;
use Syriable\Filament\Plugins\AdvancedComponents\Separator\Enums\Orientation;
use Syriable\Filament\Plugins\AdvancedComponents\Separator\Enums\SeparatorVariant;
use Syriable\Filament\Plugins\AdvancedComponents\Tests\Fixtures\SchemaLivewireComponent;

function renderSeparator(Separator $separator): string
{
    return $separator
        ->container(Schema::make(new SchemaLivewireComponent))
        ->toHtml();
}

it('has no field name and never binds to state, unlike Entry/Field', function () {
    // Entry and Field both mix in `HasName` for their state binding; a
    // Separator, being pure layout, does not.
    expect(method_exists(Separator::class, 'name'))->toBeFalse();
});

it('defaults to a horizontal, unlabeled, default-variant separator', function () {
    $separator = Separator::make();

    expect($separator->getOrientation())->toBe(Orientation::Horizontal)
        ->and($separator->getVariant())->toBe(SeparatorVariant::Default)
        ->and($separator->getLabel())->toBeNull()
        ->and($separator->isLabelHidden())->toBeFalse();
});

it('accepts a label through make() and label()', function () {
    expect(Separator::make('General Information')->getLabel())->toBe('General Information')
        ->and(Separator::make()->label('Billing')->getLabel())->toBe('Billing');
});

it('evaluates a dynamic label lazily', function () {
    $separator = Separator::make()->label(fn (): string => 'Profile');

    expect($separator->getLabel())->toBe('Profile');
});

it('hides the label without removing the separator', function () {
    $separator = Separator::make('Billing')->hiddenLabel();

    expect($separator->isLabelHidden())->toBeTrue();

    $html = renderSeparator($separator);

    expect($html)->toContain('fi-separator')
        ->and($html)->not->toContain('Billing');
});

it('text() is an alias for label()', function () {
    expect(Separator::make()->text('OR')->getLabel())->toBe('OR');
});

it('supports horizontal() and vertical() orientation toggles', function () {
    expect(Separator::make()->vertical()->getOrientation())->toBe(Orientation::Vertical)
        ->and(Separator::make()->vertical()->horizontal()->getOrientation())->toBe(Orientation::Horizontal)
        ->and(Separator::make()->orientation('vertical')->getOrientation())->toBe(Orientation::Vertical);
});

it('supports every built-in variant shorthand', function () {
    expect(Separator::make()->subtle()->getVariant())->toBe(SeparatorVariant::Subtle)
        ->and(Separator::make()->muted()->getVariant())->toBe(SeparatorVariant::Muted)
        ->and(Separator::make()->solid()->getVariant())->toBe(SeparatorVariant::Solid)
        ->and(Separator::make()->dashed()->getVariant())->toBe(SeparatorVariant::Dashed)
        ->and(Separator::make()->dotted()->getVariant())->toBe(SeparatorVariant::Dotted)
        ->and(Separator::make()->dashed()->default()->getVariant())->toBe(SeparatorVariant::Default);
});

it('accepts a custom variant name for extensibility', function () {
    $separator = Separator::make()->variant('brand');

    expect($separator->getVariant())->toBe('brand')
        ->and($separator->getVariantClass())->toBe('fi-separator-variant-brand');
});

it('supports icon and iconPosition', function () {
    $separator = Separator::make('Account')->icon('heroicon-o-user')->iconPosition('after');

    expect($separator->getIcon())->toBe('heroicon-o-user')
        ->and($separator->getIconPosition())->toBe(IconPosition::After);
});

it('resolves spaceBefore/spaceAfter from margin() unless overridden', function () {
    $separator = Separator::make()->margin(16);

    expect($separator->getSpaceBefore())->toBe('16px')
        ->and($separator->getSpaceAfter())->toBe('16px');

    $separator->spaceAfter('2rem');

    expect($separator->getSpaceBefore())->toBe('16px')
        ->and($separator->getSpaceAfter())->toBe('2rem');
});

it('resolves size presets and raw lengths for spacing', function () {
    expect(Separator::make()->margin('lg')->getMargin())->toBe('1.5rem')
        ->and(Separator::make()->padding('2rem')->getPadding())->toBe('2rem')
        ->and(Separator::make()->padding(8)->getPadding())->toBe('8px');
});

it('supports width() and fullWidth()', function () {
    expect(Separator::make()->width(240)->getWidth())->toBe('240px')
        ->and(Separator::make()->width('50%')->getWidth())->toBe('50%')
        ->and(Separator::make()->width(240)->fullWidth()->getWidth())->toBe('100%');
});

it('renders a plain divider with a single line and no content wrapper', function () {
    $html = renderSeparator(Separator::make());

    expect($html)->toContain('fi-separator')
        ->and($html)->toContain('role="separator"')
        ->and($html)->toContain('aria-orientation="horizontal"')
        ->and($html)->not->toContain('fi-separator-content');
});

it('renders the label and splits the line around it', function () {
    $html = renderSeparator(Separator::make('General Information'));

    expect($html)->toContain('fi-separator-content')
        ->and($html)->toContain('General Information')
        ->and(substr_count($html, 'fi-separator-line'))->toBe(2);
});

it('renders Htmlable labels without escaping and plain strings with escaping', function () {
    $html = renderSeparator(Separator::make(new HtmlString('<strong>Billing</strong>')));

    expect($html)->toContain('<strong>Billing</strong>');

    $escaped = renderSeparator(Separator::make('<script>alert(1)</script>'));

    expect($escaped)->not->toContain('<script>alert(1)</script>')
        ->and($escaped)->toContain('&lt;script&gt;');
});

it('renders the configured variant and orientation classes', function () {
    $html = renderSeparator(Separator::make()->dashed()->vertical());

    expect($html)->toContain('fi-separator-variant-dashed')
        ->and($html)->toContain('fi-separator-vertical')
        ->and($html)->toContain('aria-orientation="vertical"');
});

it('renders extra attributes', function () {
    $html = renderSeparator(Separator::make()->extraAttributes(['data-testid' => 'my-separator']));

    expect($html)->toContain('data-testid="my-separator"');
});

it('is hidden via the inherited hidden()/visible() API', function () {
    expect(Separator::make()->hidden()->isVisible())->toBeFalse()
        ->and(Separator::make()->visible(false)->isVisible())->toBeFalse()
        ->and(Separator::make()->isVisible())->toBeTrue();
});

it('renders through the rebindable RendersSeparator contract', function () {
    $this->app->bind(
        RendersSeparator::class,
        fn () => new class implements RendersSeparator
        {
            public function render(Separator $separator): string
            {
                return '<div class="custom-separator"></div>';
            }
        },
    );

    expect(renderSeparator(Separator::make()))->toBe('<div class="custom-separator"></div>');
});
