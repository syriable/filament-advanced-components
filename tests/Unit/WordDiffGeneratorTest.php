<?php

declare(strict_types=1);

use Syriable\Filament\Plugins\AdvancedComponents\Diff\DataTransferObjects\WordDiffToken;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\Enums\DiffLineType;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\Support\WordDiffGenerator;

describe('identical inputs', function () {
    it('produces a single unchanged token for identical strings', function () {
        $tokens = WordDiffGenerator::diff('The quick fox.', 'The quick fox.');

        expect($tokens)->toHaveCount(1)
            ->and($tokens[0]->type)->toBe(DiffLineType::Context)
            ->and($tokens[0]->text)->toBe('The quick fox.');
    });

    it('returns no tokens when both strings are empty', function () {
        expect(WordDiffGenerator::diff('', ''))->toBe([]);
    });
});

describe('additions, deletions, and mixed changes', function () {
    it('diffs a pure addition as new content appended to unchanged context', function () {
        $tokens = WordDiffGenerator::diff('Hello', 'Hello world');

        $types = array_map(fn (WordDiffToken $token): DiffLineType => $token->type, $tokens);

        expect($types)->toBe([DiffLineType::Context, DiffLineType::Addition])
            ->and($tokens[1]->text)->toBe(' world');
    });

    it('diffs a pure deletion as removed content', function () {
        $tokens = WordDiffGenerator::diff('Hello world', 'Hello');

        $types = array_map(fn (WordDiffToken $token): DiffLineType => $token->type, $tokens);

        expect($types)->toBe([DiffLineType::Context, DiffLineType::Deletion])
            ->and($tokens[1]->text)->toBe(' world');
    });

    it('matches the reference case: a trailing word replaced by a longer phrase', function () {
        $tokens = WordDiffGenerator::diff(
            'The :attribute test.',
            'The :attribute field must be a valid URL.',
        );

        $types = array_map(fn (WordDiffToken $token): DiffLineType => $token->type, $tokens);

        expect($types)->toBe([
            DiffLineType::Context,
            DiffLineType::Deletion,
            DiffLineType::Addition,
        ])
            ->and($tokens[0]->text)->toBe('The :attribute ')
            ->and($tokens[1]->text)->toBe('test.')
            ->and($tokens[2]->text)->toBe('field must be a valid URL.');
    });

    it('merges a multi-word replacement into one deletion token and one addition token', function () {
        $tokens = WordDiffGenerator::diff('red green blue', 'red purple orange blue');

        $types = array_map(fn (WordDiffToken $token): DiffLineType => $token->type, $tokens);

        expect($types)->toBe([
            DiffLineType::Context,
            DiffLineType::Deletion,
            DiffLineType::Addition,
            DiffLineType::Context,
        ])
            ->and($tokens[1]->text)->toBe('green')
            ->and($tokens[2]->text)->toBe('purple orange');
    });
});

describe('empty sides', function () {
    it('diffs an empty old side as the new content entirely added, unlike DiffGenerator', function () {
        $tokens = WordDiffGenerator::diff('', 'brand new value');

        expect($tokens)->toHaveCount(1)
            ->and($tokens[0]->type)->toBe(DiffLineType::Addition)
            ->and($tokens[0]->text)->toBe('brand new value');
    });

    it('diffs an empty new side as the old content entirely removed', function () {
        $tokens = WordDiffGenerator::diff('old value', '');

        expect($tokens)->toHaveCount(1)
            ->and($tokens[0]->type)->toBe(DiffLineType::Deletion)
            ->and($tokens[0]->text)->toBe('old value');
    });
});

describe('reassembly', function () {
    it('reassembles the tokens back into the exact old and new strings', function () {
        $old = 'The  quick   brown fox';
        $new = 'The  quick   red fox jumps';

        $tokens = WordDiffGenerator::diff($old, $new);

        $oldReassembled = implode('', array_map(
            fn (WordDiffToken $token): string => $token->type === DiffLineType::Addition ? '' : $token->text,
            $tokens,
        ));

        $newReassembled = implode('', array_map(
            fn (WordDiffToken $token): string => $token->type === DiffLineType::Deletion ? '' : $token->text,
            $tokens,
        ));

        expect($oldReassembled)->toBe($old)
            ->and($newReassembled)->toBe($new);
    });

    it('registers a changed whitespace run as its own small deletion/addition pair, not a word change', function () {
        $tokens = WordDiffGenerator::diff('Hello   world', 'Hello world');

        $types = array_map(fn (WordDiffToken $token): DiffLineType => $token->type, $tokens);

        expect($types)->toBe([
            DiffLineType::Context,
            DiffLineType::Deletion,
            DiffLineType::Addition,
            DiffLineType::Context,
        ])
            ->and($tokens[1]->text)->toBe('   ')
            ->and($tokens[2]->text)->toBe(' ');
    });
});
