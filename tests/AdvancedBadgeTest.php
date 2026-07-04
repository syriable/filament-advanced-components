<?php

declare(strict_types=1);

use Filament\Support\Colors\Color;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Badges\AdvancedBadge;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Badges\BadgeAnimations;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Badges\BadgeRenderer;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Contracts\RendersBadges;
use Syriable\Filament\Plugins\AdvancedComponents\Infolists\Components\AdvancedTextEntry;
use Syriable\Filament\Plugins\AdvancedComponents\Tables\Columns\AdvancedTextColumn;
use Syriable\Filament\Plugins\AdvancedComponents\Tests\Fixtures\Contact;

it('renders multiple independent badges after the content', function () {
    $html = renderCell(
        AdvancedTextColumn::make('status')
            ->state('Jane')
            ->badges([
                AdvancedBadge::make('Verified')->color('success'),
                AdvancedBadge::make('Premium')->color('warning'),
            ]),
    );

    expect(substr_count($html, 'fi-badge'))->toBeGreaterThanOrEqual(2)
        ->and($html)->toContain('Verified')
        ->and($html)->toContain('Premium')
        ->and($html)->toContain('fi-color-success')
        ->and($html)->toContain('fi-color-warning')
        ->and($html)->toContain('fi-adv-badges')
        ->and(strpos($html, 'Jane'))->toBeLessThan(strpos($html, 'Verified'));
});

it('normalizes plain strings into label-only badges', function () {
    $html = renderCell(
        AdvancedTextColumn::make('status')->state('Jane')->badges(['Admin', 'Online']),
    );

    expect($html)->toContain('Admin')
        ->and($html)->toContain('Online')
        ->and(substr_count($html, 'fi-badge'))->toBeGreaterThanOrEqual(2);
});

it('accepts a closure returning the badge list', function () {
    $html = renderCell(
        AdvancedTextColumn::make('status')
            ->state('Jane')
            ->badges(fn (Contact $record): array => [
                AdvancedBadge::make($record->name),
            ]),
        ['name' => 'Closure Badge'],
    );

    expect($html)->toContain('Closure Badge');
});

describe('dynamic configuration', function () {
    it('evaluates labels, colors, and tooltips lazily with record access', function () {
        $badge = AdvancedBadge::make(fn (Contact $record): string => strtoupper($record->name))
            ->color(fn (Contact $record): string => $record->is_admin ? 'success' : 'danger')
            ->tooltip(fn (Contact $record): string => "User: {$record->name}");

        $html = renderCell(
            AdvancedTextColumn::make('status')->state('x')->badges([$badge]),
            ['name' => 'jane', 'is_admin' => true],
        );

        expect($html)->toContain('JANE')
            ->and($html)->toContain('fi-color-success')
            ->and($html)->toContain('x-tooltip')
            ->and($html)->toContain('User: jane');

        // The same badge instance resolves independently for another record.
        $html = renderCell(
            AdvancedTextColumn::make('status')->state('x')->badges([$badge]),
            ['name' => 'joe', 'is_admin' => false],
        );

        expect($html)->toContain('JOE')
            ->and($html)->toContain('fi-color-danger');
    });

    it('hides badges via visibility conditions', function () {
        $html = renderCell(
            AdvancedTextColumn::make('status')->state('x')->badges([
                AdvancedBadge::make('Visible'),
                AdvancedBadge::make('Invisible')->visible(fn (): bool => false),
                AdvancedBadge::make('Hidden')->hidden(),
                AdvancedBadge::make(fn (): ?string => null),
            ]),
        );

        expect($html)->toContain('Visible')
            ->and($html)->not->toContain('Invisible')
            ->and($html)->not->toContain('Hidden');
    });

    it('hides badges via authorization conditions', function () {
        Gate::define('see-badge', fn (?object $user = null): bool => false);

        $html = renderCell(
            AdvancedTextColumn::make('status')->state('x')->badges([
                AdvancedBadge::make('Gated')->authorize('see-badge'),
                AdvancedBadge::make('Closed')->authorize(fn (): bool => false),
                AdvancedBadge::make('Open')->authorize(fn (): bool => true),
            ]),
        );

        expect($html)->not->toContain('Gated')
            ->and($html)->not->toContain('Closed')
            ->and($html)->toContain('Open');
    });

    it('renders no badge container when every badge is hidden', function () {
        $html = renderCell(
            AdvancedTextColumn::make('status')->state('x')->badges([
                AdvancedBadge::make('Nope')->hidden(),
            ]),
        );

        expect($html)->not->toContain('fi-adv-badges');
    });
});

describe('styling', function () {
    it('resolves palette array colors into inline custom styles', function () {
        $html = renderCell(
            AdvancedTextColumn::make('status')->state('x')->badges([
                AdvancedBadge::make('Purple')->color(Color::Purple),
            ]),
        );

        expect($html)->toContain('fi-color')
            ->and($html)->toContain('--text:');
    });

    it('supports background, text, and border overrides', function () {
        $html = renderCell(
            AdvancedTextColumn::make('status')->state('x')->badges([
                AdvancedBadge::make('Custom')
                    ->backgroundColor('#111827')
                    ->textColor('success')
                    ->borderColor('danger')
                    ->borderWidth(2)
                    ->borderRadius(4),
            ]),
        );

        expect($html)->toContain('background-color: #111827')
            ->and($html)->toContain('color: var(--color-success-600)')
            ->and($html)->toContain('border-color: var(--color-danger-600)')
            ->and($html)->toContain('border-width: 2px')
            ->and($html)->toContain('border-style: solid')
            ->and($html)->toContain('border-radius: 4px');
    });

    it('applies a default 1px border via border()', function () {
        $html = renderCell(
            AdvancedTextColumn::make('status')->state('x')->badges([
                AdvancedBadge::make('Bordered')->border(),
            ]),
        );

        expect($html)->toContain('border-width: 1px');
    });

    it('supports pill, rounded, outline, and filled styles', function () {
        $html = renderCell(
            AdvancedTextColumn::make('status')->state('x')->badges([
                AdvancedBadge::make('Pill')->pill(),
                AdvancedBadge::make('Rounded')->rounded(),
                AdvancedBadge::make('Outline')->outline(),
                AdvancedBadge::make('Filled')->outline()->filled(),
            ]),
        );

        expect($html)->toContain('fi-adv-badge-pill')
            ->and($html)->toContain('border-radius: 0.375rem')
            ->and(substr_count($html, 'fi-adv-badge-outline'))->toBe(1);
    });

    it('supports sizes and custom classes', function () {
        $html = renderCell(
            AdvancedTextColumn::make('status')->state('x')->badges([
                AdvancedBadge::make('Big')->size('lg')->classes(['my-badge']),
            ]),
        );

        expect($html)->toContain('fi-size-lg')
            ->and($html)->toContain('my-badge');
    });
});

describe('animations', function () {
    it('applies pulse and bounce conditionally', function () {
        $html = renderCell(
            AdvancedTextColumn::make('status')->state('x')->badges([
                AdvancedBadge::make('Pulse')->pulse(),
                AdvancedBadge::make('Bounce')->bounce(),
                AdvancedBadge::make('Still')->pulse(fn (): bool => false),
            ]),
        );

        expect(substr_count($html, 'fi-adv-badge-pulse'))->toBe(1)
            ->and(substr_count($html, 'fi-adv-badge-bounce'))->toBe(1);
    });

    it('supports registering custom animations', function () {
        BadgeAnimations::register('wiggle', 'my-wiggle-class');

        $html = renderCell(
            AdvancedTextColumn::make('status')->state('x')->badges([
                AdvancedBadge::make('Wiggly')->animation('wiggle'),
            ]),
        );

        expect($html)->toContain('my-wiggle-class');
    });

    it('rejects unregistered animations', function () {
        renderCell(
            AdvancedTextColumn::make('status')->state('x')->badges([
                AdvancedBadge::make('Broken')->animation('nope'),
            ]),
        );
    })->throws(InvalidArgumentException::class);
});

describe('icons', function () {
    it('renders icons before the label by default and after when configured', function () {
        $before = renderCell(
            AdvancedTextColumn::make('status')->state('x')->badges([
                AdvancedBadge::make('Iconed')->icon(Heroicon::Check),
            ]),
        );

        expect(strpos($before, 'fi-icon'))->toBeLessThan(strpos($before, 'Iconed'));

        $after = renderCell(
            AdvancedTextColumn::make('status')->state('x')->badges([
                AdvancedBadge::make('Iconed')->icon(Heroicon::Check, IconPosition::After),
            ]),
        );

        expect(strpos($after, 'fi-icon'))->toBeGreaterThan(strpos($after, 'Iconed'));
    });

    it('colors the icon independently', function () {
        $html = renderCell(
            AdvancedTextColumn::make('status')->state('x')->badges([
                AdvancedBadge::make('Iconed')->icon(Heroicon::Check)->iconColor('danger'),
            ]),
        );

        expect($html)->toContain('color: var(--color-danger-600)');
    });
});

describe('interaction', function () {
    it('renders url badges as anchors, optionally in a new tab', function () {
        $html = renderCell(
            AdvancedTextColumn::make('status')->state('x')->badges([
                AdvancedBadge::make('Link')->url('https://example.com', shouldOpenInNewTab: true),
            ]),
        );

        expect($html)->toContain('<a ')
            ->and($html)->toContain('href="https://example.com"')
            ->and($html)->toContain('target="_blank"');
    });

    it('supports Livewire clicks with keyboard-accessible button semantics', function () {
        $html = renderCell(
            AdvancedTextColumn::make('status')->state('x')->badges([
                AdvancedBadge::make('Action')->wireClick('doSomething'),
            ]),
        );

        expect($html)->toContain('wire:click="doSomething"')
            ->and($html)->toContain('role="button"')
            ->and($html)->toContain('tabindex="0"')
            ->and($html)->toContain('x-on:keydown.enter.prevent');
    });

    it('supports Alpine clicks and custom attributes', function () {
        $html = renderCell(
            AdvancedTextColumn::make('status')->state('x')->badges([
                AdvancedBadge::make('Alpine')
                    ->alpineClick('open = ! open')
                    ->extraAttributes(['data-badge' => 'alpine']),
            ]),
        );

        expect($html)->toContain('x-on:click="open = ! open"')
            ->and($html)->toContain('data-badge="alpine"');
    });
});

describe('extensibility', function () {
    it('supports macros', function () {
        AdvancedBadge::macro('danger', function () {
            /** @var AdvancedBadge $this */
            return $this->color('danger')->pulse();
        });

        $html = renderCell(
            AdvancedTextColumn::make('status')->state('x')->badges([
                AdvancedBadge::make('Banned')->danger(),
            ]),
        );

        expect($html)->toContain('fi-color-danger')
            ->and($html)->toContain('fi-adv-badge-pulse');
    });

    it('supports reusable presets', function () {
        AdvancedBadge::registerPreset('pii', fn (AdvancedBadge $badge) => $badge
            ->color('danger')
            ->outline());

        $html = renderCell(
            AdvancedTextColumn::make('status')->state('x')->badges([
                AdvancedBadge::make('Sensitive')->preset('pii'),
            ]),
        );

        expect($html)->toContain('fi-color-danger')
            ->and($html)->toContain('fi-adv-badge-outline');
    });

    it('rejects unregistered presets', function () {
        AdvancedBadge::make('X')->preset('missing');
    })->throws(InvalidArgumentException::class);

    it('lets a rebound renderer change badge output', function () {
        app()->singleton(RendersBadges::class, function (): RendersBadges {
            return new class extends BadgeRenderer
            {
                public function renderCollection(iterable $badges): string
                {
                    return '<span class="custom-badges">swapped</span>';
                }
            };
        });

        try {
            $html = renderCell(
                AdvancedTextColumn::make('status')->state('x')->badges(['Anything']),
            );

            expect($html)->toContain('custom-badges');
        } finally {
            app()->singleton(RendersBadges::class, BadgeRenderer::class);
        }
    });
});

it('escapes badge labels', function () {
    $html = renderCell(
        AdvancedTextColumn::make('status')->state('x')->badges([
            AdvancedBadge::make('<script>alert(1)</script>'),
        ]),
    );

    expect($html)->not->toContain('<script>')
        ->and($html)->toContain('&lt;script&gt;');
});

it('translates labels through the translator', function () {
    app('translator')->addLines(['badges.verified' => 'Vérifié'], app()->getLocale());

    $html = renderCell(
        AdvancedTextColumn::make('status')->state('x')->badges([
            AdvancedBadge::make('badges.verified')->translateLabel(),
        ]),
    );

    expect($html)->toContain('Vérifié');
});

it('renders badges on infolist entries too', function () {
    $html = renderEntry(
        AdvancedTextEntry::make('status')->state('x')->badges([
            AdvancedBadge::make('Entry Badge')->color('info'),
        ]),
    );

    expect($html)->toContain('Entry Badge')
        ->and($html)->toContain('fi-color-info');
});

describe('affix image fit', function () {
    it('defaults to contain via the stylesheet', function () {
        expect(file_get_contents(__DIR__ . '/../resources/css/advanced-text.css'))
            ->toContain('object-fit: contain');
    });

    it('renders a dynamic object-fit inline style', function () {
        $html = renderCell(
            AdvancedTextColumn::make('name')
                ->state('Jane')
                ->prefixImage('https://example.com/a.png')
                ->imageFit(fn (): string => 'cover'),
        );

        expect($html)->toContain('object-fit: cover;');
    });

    it('renders no inline object-fit when not configured', function () {
        $html = renderCell(
            AdvancedTextColumn::make('name')
                ->state('Jane')
                ->prefixImage('https://example.com/a.png'),
        );

        expect($html)->not->toContain('object-fit');
    });
});
