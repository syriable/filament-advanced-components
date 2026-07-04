<?php

declare(strict_types=1);

use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Contracts\GeneratesLinks;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Contracts\MasksText;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Support\LinkGenerator;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Support\TextMasker;
use Syriable\Filament\Plugins\AdvancedComponents\Tables\Columns\AdvancedTextColumn;

describe('TextMasker', function () {
    it('masks the whole string by default', function () {
        expect((new TextMasker)->mask('secret'))->toBe('••••••');
    });

    it('masks from an index for a length', function () {
        expect((new TextMasker)->mask('1234567890', '*', 3, 4))->toBe('123****890');
    });

    it('clamps overflowing lengths', function () {
        expect((new TextMasker)->mask('abc', '*', 1, 99))->toBe('a**');
    });

    it('supports negative indexes', function () {
        expect((new TextMasker)->mask('abcdef', '*', -2))->toBe('abcd**');
    });

    it('ignores out-of-range indexes', function () {
        expect((new TextMasker)->mask('abc', '*', 10))->toBe('abc');
    });

    it('ignores zero and negative lengths', function () {
        expect((new TextMasker)->mask('abc', '*', 0, 0))->toBe('abc');
    });

    it('handles empty strings', function () {
        expect((new TextMasker)->mask(''))->toBe('');
    });

    it('is multibyte-safe', function () {
        expect((new TextMasker)->mask('héllo', '•', 1, 2))->toBe('h••lo');
    });

    it('masks the local part of emails', function () {
        expect((new TextMasker)->maskEmail('jane.doe@example.com'))->toBe('j•••••••@example.com');
    });

    it('keeps at least one masked character for single-letter locals', function () {
        expect((new TextMasker)->maskEmail('j@example.com'))->toBe('j•@example.com');
    });

    it('falls back to near-full masking for non-emails', function () {
        expect((new TextMasker)->maskEmail('not-an-email'))->toBe('n•••••••••••');
    });
});

describe('LinkGenerator', function () {
    it('generates mailto links for valid emails', function () {
        expect((new LinkGenerator)->mailto(' jane@example.com '))->toBe('mailto:jane@example.com');
    });

    it('rejects invalid emails', function () {
        expect((new LinkGenerator)->mailto('nope'))->toBeNull();
    });

    it('generates tel links keeping only dialable characters', function () {
        expect((new LinkGenerator)->tel('+49 (123) 456-789'))->toBe('tel:+49123456789');
    });

    it('rejects phone numbers without digits', function () {
        expect((new LinkGenerator)->tel('call me'))->toBeNull();
    });

    it('generates WhatsApp links with digits only', function () {
        expect((new LinkGenerator)->whatsapp('+49 123 456'))->toBe('https://wa.me/49123456');
    });

    it('encodes the prefilled WhatsApp message', function () {
        expect((new LinkGenerator)->whatsapp('49123', 'Hi & bye'))
            ->toBe('https://wa.me/49123?text=Hi%20%26%20bye');
    });

    it('rejects WhatsApp numbers without digits', function () {
        expect((new LinkGenerator)->whatsapp('nope'))->toBeNull();
    });
});

describe('container bindings', function () {
    it('binds the contracts as singletons', function () {
        expect(app(MasksText::class))->toBeInstanceOf(TextMasker::class)
            ->and(app(GeneratesLinks::class))->toBeInstanceOf(LinkGenerator::class)
            ->and(app(MasksText::class))->toBe(app(MasksText::class));
    });

    it('lets a rebound masker change column behavior', function () {
        app()->singleton(MasksText::class, function (): MasksText {
            return new class implements MasksText
            {
                public function mask(string $text, string $character = '•', int $index = 0, ?int $length = null): string
                {
                    return '[redacted]';
                }

                public function maskEmail(string $email, string $character = '•'): string
                {
                    return '[redacted]';
                }
            };
        });

        try {
            $html = renderCell(
                AdvancedTextColumn::make('secret')
                    ->state('hunter2')
                    ->masked(),
            );

            expect($html)->toContain('[redacted]');
        } finally {
            app()->singleton(MasksText::class, TextMasker::class);
        }
    });
});
