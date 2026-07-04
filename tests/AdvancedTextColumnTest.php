<?php

declare(strict_types=1);

use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\HtmlString;
use Livewire\Livewire;
use Syriable\Filament\Plugins\AdvancedComponents\Tables\Columns\AdvancedTextColumn;
use Syriable\Filament\Plugins\AdvancedComponents\Tests\Fixtures\Contact;
use Syriable\Filament\Plugins\AdvancedComponents\Tests\Fixtures\ContactsTableComponent;

it('renders exactly like a native text column when no advanced feature is used', function () {
    $html = renderCell(AdvancedTextColumn::make('email')->state('jane@example.com'));

    expect($html)->toContain('jane@example.com')
        ->and($html)->toContain('fi-ta-text')
        ->and($html)->not->toContain('fi-adv-text');
});

it('escapes HTML by default', function () {
    $html = renderCell(AdvancedTextColumn::make('bio')->state('<script>alert(1)</script>'));

    expect($html)->not->toContain('<script>')
        ->and($html)->toContain('&lt;script&gt;');
});

it('renders sanitized HTML when explicitly enabled', function () {
    $html = renderCell(
        AdvancedTextColumn::make('bio')
            ->state('<strong>Hi</strong><script>alert(1)</script>')
            ->html(),
    );

    expect($html)->toContain('<strong>Hi</strong>')
        ->and($html)->not->toContain('<script>');
});

describe('masking', function () {
    it('masks the whole state by default', function () {
        $html = renderCell(AdvancedTextColumn::make('secret')->state('hunter2')->masked());

        expect($html)->toContain(str_repeat('•', 7))
            ->and($html)->not->toContain('hunter2');
    });

    it('masks a partial range with a custom character', function () {
        $html = renderCell(
            AdvancedTextColumn::make('phone')
                ->state('+49123456789')
                ->masked()
                ->maskIndex(3)
                ->maskLength(5)
                ->maskCharacter('*'),
        );

        expect($html)->toContain('+49*****6789');
    });

    it('supports a negative mask index counting from the end', function () {
        $html = renderCell(
            AdvancedTextColumn::make('iban')
                ->state('DE00123456')
                ->masked()
                ->maskIndex(-4),
        );

        expect($html)->toContain('DE0012••••');
    });

    it('masks emails while keeping them recognizable', function () {
        $html = renderCell(
            AdvancedTextColumn::make('email')
                ->state('jane.doe@example.com')
                ->maskEmail(),
        );

        expect($html)->toContain('j•••••••@example.com')
            ->and($html)->not->toContain('jane.doe@example.com');
    });

    it('evaluates the masked condition lazily with record access', function () {
        $html = renderCell(
            AdvancedTextColumn::make('secret')
                ->state('hunter2')
                ->masked(fn (Contact $record): bool => (bool) $record->is_admin),
            ['is_admin' => false],
        );

        expect($html)->toContain('hunter2');

        $html = renderCell(
            AdvancedTextColumn::make('secret')
                ->state('hunter2')
                ->masked(fn (Contact $record): bool => (bool) $record->is_admin),
            ['is_admin' => true],
        );

        expect($html)->not->toContain('hunter2');
    });

    it('lets a custom masking closure take full control', function () {
        $html = renderCell(
            AdvancedTextColumn::make('secret')
                ->state('hunter2')
                ->maskStateUsing(fn (string $state): string => strrev($state)),
        );

        expect($html)->toContain('2retnuh');
    });

    it('masks after native formatting so formatters see the raw value', function () {
        $html = renderCell(
            AdvancedTextColumn::make('secret')
                ->state('abc')
                ->formatStateUsing(fn (string $state): string => strtoupper($state) . '!')
                ->masked()
                ->maskIndex(3),
        );

        expect($html)->toContain('ABC•');
    });

    it('reduces HTML states to plain text before masking', function () {
        $html = renderCell(
            AdvancedTextColumn::make('bio')
                ->state('<strong>secret</strong>')
                ->html()
                ->masked()
                ->maskIndex(2),
        );

        expect($html)->toContain('se••••')
            ->and($html)->not->toContain('<strong>');
    });

    it('copies the masked state instead of leaking the raw value', function () {
        $html = renderCell(
            AdvancedTextColumn::make('secret')
                ->state('hunter2')
                ->masked()
                ->copyable(),
        );

        expect($html)->toContain('navigator.clipboard.writeText')
            ->and($html)->not->toContain('hunter2');
    });

    it('still copies an explicit copyable state', function () {
        $html = renderCell(
            AdvancedTextColumn::make('secret')
                ->state('hunter2')
                ->masked()
                ->copyable()
                ->copyableState('custom-copy'),
        );

        expect($html)->toContain('custom-copy');
    });

    it('handles null and empty states gracefully', function () {
        expect(renderCell(AdvancedTextColumn::make('secret')->masked()->placeholder('Empty')))
            ->toContain('Empty');

        expect(renderCell(AdvancedTextColumn::make('secret')->state('')->masked()->placeholder('Empty')))
            ->toContain('Empty');
    });
});

describe('contact links', function () {
    it('links emails with mailto', function () {
        $html = renderCell(AdvancedTextColumn::make('email')->state('jane@example.com')->mailable());

        expect($html)->toContain('href="mailto:jane@example.com"');
    });

    it('degrades to plain text for invalid emails', function () {
        $html = renderCell(AdvancedTextColumn::make('email')->state('not-an-email')->mailable());

        expect($html)->not->toContain('<a ')
            ->and($html)->toContain('not-an-email');
    });

    it('links phone numbers with tel and strips visual separators', function () {
        $html = renderCell(AdvancedTextColumn::make('phone')->state('+49 (123) 456-789')->callable());

        expect($html)->toContain('href="tel:+49123456789"');
    });

    it('links phone numbers to WhatsApp with a prefilled message', function () {
        $html = renderCell(
            AdvancedTextColumn::make('phone')
                ->state('+49 123 456789')
                ->whatsappable(message: 'Hi there!'),
        );

        expect($html)->toContain('href="https://wa.me/49123456789?text=Hi%20there%21"');
    });

    it('never overrides an explicit url', function () {
        $html = renderCell(
            AdvancedTextColumn::make('email')
                ->state('jane@example.com')
                ->mailable()
                ->url(fn ($state): string => 'https://example.com/custom'),
        );

        expect($html)->toContain('href="https://example.com/custom"')
            ->and($html)->not->toContain('mailto:');
    });

    it('does not generate a link that would leak a masked state', function () {
        $html = renderCell(
            AdvancedTextColumn::make('email')
                ->state('jane@example.com')
                ->mailable()
                ->masked(),
        );

        expect($html)->not->toContain('mailto:')
            ->and($html)->not->toContain('jane@example.com');
    });

    it('evaluates link conditions lazily', function () {
        $html = renderCell(
            AdvancedTextColumn::make('email')
                ->state('jane@example.com')
                ->mailable(fn (): bool => false),
        );

        expect($html)->not->toContain('mailto:');
    });
});

describe('affixes', function () {
    it('renders a prefix image with sizing and lazy loading', function () {
        $html = renderCell(
            AdvancedTextColumn::make('name')
                ->state('Jane')
                ->prefixImage('https://example.com/avatar.png')
                ->imageSize(32),
        );

        expect($html)->toContain('src="https://example.com/avatar.png"')
            ->and($html)->toContain('width: 32px; height: 32px;')
            ->and($html)->toContain('loading="lazy"')
            ->and($html)->toContain('fi-adv-text-affixed')
            ->and(strpos($html, '<img'))->toBeLessThan(strpos($html, 'Jane'));
    });

    it('renders a suffix image after the content', function () {
        $html = renderCell(
            AdvancedTextColumn::make('name')
                ->state('Jane')
                ->suffixImage('https://example.com/badge.png'),
        );

        expect(strpos($html, '<img'))->toBeGreaterThan(strpos($html, 'Jane'));
    });

    it('renders circular avatars', function () {
        $html = renderCell(
            AdvancedTextColumn::make('name')
                ->state('Jane')
                ->prefixImage('https://example.com/avatar.png')
                ->imageCircular(),
        );

        expect($html)->toContain('border-radius: 50%;');
    });

    it('supports custom rounding', function () {
        $html = renderCell(
            AdvancedTextColumn::make('name')
                ->state('Jane')
                ->prefixImage('https://example.com/avatar.png')
                ->imageRounded(4),
        );

        expect($html)->toContain('border-radius: 4px;');
    });

    it('escapes image attributes', function () {
        $html = renderCell(
            AdvancedTextColumn::make('name')
                ->state('Jane')
                ->prefixImage('https://example.com/a.png" onerror="alert(1)'),
        );

        expect($html)->not->toContain('onerror="alert(1)"')
            ->and($html)->toContain('&quot;');
    });

    it('renders prefix and suffix icons at the same time', function () {
        $html = renderCell(
            AdvancedTextColumn::make('name')
                ->state('Jane')
                ->prefixIcon(Heroicon::User)
                ->suffixIcon(Heroicon::CheckBadge, color: 'success'),
        );

        expect(substr_count($html, 'fi-icon'))->toBeGreaterThanOrEqual(2);
    });

    it('evaluates image urls against the state', function () {
        $html = renderCell(
            AdvancedTextColumn::make('name')
                ->state('Jane')
                ->prefixImage(fn ($state): string => "https://example.com/{$state}.png"),
        );

        expect($html)->toContain('src="https://example.com/Jane.png"');
    });
});

describe('typography', function () {
    it('applies typography classes conditionally', function () {
        $html = renderCell(
            AdvancedTextColumn::make('name')
                ->state('Jane')
                ->bold()
                ->italic()
                ->underline(fn (): bool => true)
                ->strikethrough(fn (): bool => false),
        );

        expect($html)->toContain('fi-adv-text-bold')
            ->and($html)->toContain('fi-adv-text-italic')
            ->and($html)->toContain('fi-adv-text-underline')
            ->and($html)->not->toContain('fi-adv-text-strike');
    });

    it('supports text transforms', function () {
        expect(renderCell(AdvancedTextColumn::make('name')->state('Jane')->uppercase()))
            ->toContain('fi-adv-text-uppercase');

        expect(renderCell(AdvancedTextColumn::make('name')->state('Jane')->lowercase()))
            ->toContain('fi-adv-text-lowercase');

        expect(renderCell(AdvancedTextColumn::make('name')->state('Jane')->capitalize()))
            ->toContain('fi-adv-text-capitalize');
    });

    it('composes with native weight and size APIs', function () {
        $html = renderCell(
            AdvancedTextColumn::make('name')
                ->state('Jane')
                ->weight(FontWeight::SemiBold)
                ->italic(),
        );

        expect($html)->toContain('fi-font-semibold')
            ->and($html)->toContain('fi-adv-text-italic');
    });
});

describe('character count', function () {
    it('shows the character count', function () {
        $html = renderCell(AdvancedTextColumn::make('bio')->state('Hello')->characterCount());

        expect($html)->toContain('fi-adv-text-character-count')
            ->and($html)->toContain('>5<');
    });

    it('shows the count against a limit and flags overflows', function () {
        $html = renderCell(
            AdvancedTextColumn::make('bio')
                ->state('Hello world')
                ->characterLimitIndicator(5),
        );

        expect($html)->toContain('11 / 5')
            ->and($html)->toContain('fi-adv-text-character-count-exceeded');
    });

    it('counts the raw state even when the display is limited or masked', function () {
        $html = renderCell(
            AdvancedTextColumn::make('bio')
                ->state('Hello world')
                ->limit(5)
                ->masked()
                ->characterCount(),
        );

        expect($html)->toContain('>11<');
    });

    it('counts multibyte characters correctly', function () {
        $html = renderCell(AdvancedTextColumn::make('bio')->state('héllo')->characterCount());

        expect($html)->toContain('>5<');
    });
});

describe('native TextColumn compatibility', function () {
    it('keeps badges working, including multiple badges from separated state', function () {
        $html = renderCell(
            AdvancedTextColumn::make('tags')
                ->state('php,laravel')
                ->separator(',')
                ->badge(),
        );

        expect(substr_count($html, 'fi-badge'))->toBe(2)
            ->and($html)->toContain('php')
            ->and($html)->toContain('laravel');
    });

    it('keeps descriptions working', function () {
        $html = renderCell(
            AdvancedTextColumn::make('name')
                ->state('Jane')
                ->description('An admin user'),
        );

        expect($html)->toContain('An admin user');
    });

    it('shows the full state as a tooltip next to limit()', function () {
        $html = renderCell(
            AdvancedTextColumn::make('bio')
                ->state('Hello wonderful world')
                ->limit(5)
                ->fullStateTooltip(),
        );

        expect($html)->toContain('x-tooltip')
            ->and($html)->toContain('Hello wonderful world');
    });

    it('never reveals a masked state through the full-state tooltip', function () {
        $html = renderCell(
            AdvancedTextColumn::make('secret')
                ->state('hunter2')
                ->masked()
                ->fullStateTooltip(),
        );

        expect($html)->not->toContain('hunter2');
    });

    it('keeps tooltips working', function () {
        $html = renderCell(
            AdvancedTextColumn::make('name')
                ->state('Jane')
                ->tooltip('Full name'),
        );

        expect($html)->toContain('x-tooltip');
    });

    it('keeps placeholders working for null state', function () {
        $html = renderCell(AdvancedTextColumn::make('missing')->placeholder('No name'));

        expect($html)->toContain('No name');
    });

    it('renders an HtmlString state without double-escaping', function () {
        $html = renderCell(AdvancedTextColumn::make('name')->state(new HtmlString('<em>Jane</em>')));

        expect($html)->toContain('<em>Jane</em>');
    });
});

describe('extensibility', function () {
    it('supports html decorators', function () {
        $html = renderCell(
            AdvancedTextColumn::make('name')
                ->state('Jane')
                ->decorateHtmlUsing(fn (string $html): string => "<div class=\"wrapped\">{$html}</div>"),
        );

        expect($html)->toStartWith('<div class="wrapped">');
    });

    it('supports macros', function () {
        AdvancedTextColumn::macro('screamable', function () {
            /** @var AdvancedTextColumn $this */
            return $this->uppercase()->bold();
        });

        $html = renderCell(AdvancedTextColumn::make('name')->state('Jane')->screamable());

        expect($html)->toContain('fi-adv-text-uppercase')
            ->and($html)->toContain('fi-adv-text-bold');
    });

    it('supports global configuration', function () {
        AdvancedTextColumn::configureUsing(fn (AdvancedTextColumn $column) => $column->italic());

        try {
            $html = renderCell(AdvancedTextColumn::make('name')->state('Jane'));

            expect($html)->toContain('fi-adv-text-italic');
        } finally {
            AdvancedTextColumn::configureUsing(fn (AdvancedTextColumn $column) => $column->italic(false));
        }
    });
});

describe('Livewire integration', function () {
    beforeEach(function () {
        if (! Schema::hasTable('contacts')) {
            Schema::create('contacts', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->string('phone');
                $table->boolean('is_admin')->default(false);
            });
        }

        Contact::query()->create([
            'name' => 'Jane Admin',
            'email' => 'jane@example.com',
            'phone' => '+49123456789',
            'is_admin' => true,
        ]);

        Contact::query()->create([
            'name' => 'Joe User',
            'email' => 'joe@example.com',
            'phone' => '+31987654321',
            'is_admin' => false,
        ]);
    });

    it('renders advanced columns inside a real table', function () {
        Livewire::test(ContactsTableComponent::class)
            ->assertSee('Jane Admin')
            ->assertSee('href="mailto:jane@example.com"', escape: false)
            ->assertSee('+49*********', escape: false)
            ->assertDontSee('+49123456789')
            ->assertSee('fi-adv-text-bold', escape: false)
            ->assertSee('fi-adv-text-character-count', escape: false);
    });

    it('evaluates record-dependent closures per row', function () {
        $html = Livewire::test(ContactsTableComponent::class)->html();

        // Only the admin row is bold: the class appears exactly once.
        expect(substr_count($html, 'fi-adv-text-bold'))->toBe(1);
    });
});
