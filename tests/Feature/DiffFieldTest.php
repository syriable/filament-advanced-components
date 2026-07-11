<?php

declare(strict_types=1);

use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\Contracts\BuildsRollbackAction;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\DataTransferObjects\DiffFile;
use Syriable\Filament\Plugins\AdvancedComponents\Diff\Rollback\RollbackManager;
use Syriable\Filament\Plugins\AdvancedComponents\Forms\Components\DiffField;
use Syriable\Filament\Plugins\AdvancedComponents\Tests\Fixtures\ActionsFormLivewireComponent;
use Syriable\Filament\Plugins\AdvancedComponents\Tests\Fixtures\SchemaLivewireComponent;

beforeEach(function () {
    // Laravel's ShareErrorsFromSession middleware shares this with every view
    // in a real request; Filament's field wrapper expects it, so standalone
    // renders in tests need it too.
    View::share('errors', new ViewErrorBag);
});

/**
 * A 20-line fixture with a change at line 5 and another at line 15. With the
 * default 3 context lines this yields five hunks: collapsed leading context,
 * the first change, collapsed middle context, the second change, and
 * collapsed trailing context.
 */
function diffFieldFixture(): DiffField
{
    $old = implode("\n", array_map(fn (int $i): string => "line {$i}", range(1, 20)));
    $new = str_replace(['line 5', 'line 15'], ['line five', 'line fifteen'], $old);

    return DiffField::make('changes')
        ->container(Schema::make(new SchemaLivewireComponent))
        ->oldValue($old)
        ->newValue($new);
}

it('renders the diff inside the field wrapper with header, gutters, and row backgrounds', function () {
    $html = diffFieldFixture()
        ->filename('config/app.php')
        ->toHtml();

    expect($html)->toContain('fi-fo-field')
        ->and($html)->toContain('config/app.php')
        ->and($html)->toContain('+2')
        ->and($html)->toContain('−2')
        ->and($html)->toContain('fi-diff-field-stat-square-addition')
        ->and($html)->toContain('fi-diff-field-row-addition')
        ->and($html)->toContain('fi-diff-field-row-deletion')
        ->and($html)->toContain('fi-diff-field-row-context')
        ->and($html)->toContain('line five')
        ->and($html)->toContain('line fifteen')
        ->and($html)->toContain('fi-diff-field-gutter');
});

it('renders the expected hunk structure with collapsed context blocks', function () {
    $html = diffFieldFixture()->toHtml();

    // Leading (1 hidden), middle (3 hidden), and trailing (2 hidden) context
    // blocks collapse; the two changed regions stay visible.
    expect(substr_count($html, '<tbody'))->toBe(5)
        ->and(substr_count($html, 'fi-diff-field-hunk-collapsed'))->toBe(3)
        ->and($html)->toContain('Expand 1 hidden line')
        ->and($html)->toContain('Expand 3 hidden lines')
        ->and($html)->toContain('Expand 2 hidden lines')
        ->and(substr_count($html, 'x-data="{ expanded: false }"'))->toBe(3);
});

it('shows the filename in the header, falling back to the field label', function () {
    $withFilename = diffFieldFixture()->filename('config/app.php')->toHtml();

    expect($withFilename)->toContain('fi-diff-field-filename">config/app.php<');

    $labelled = diffFieldFixture()->label('Configuration changes')->toHtml();

    expect($labelled)->toContain('fi-diff-field-filename">Configuration changes<');
});

it('evaluates closure-based configuration', function () {
    $field = DiffField::make('changes')
        ->container(Schema::make(new SchemaLivewireComponent))
        ->oldValue(fn (): string => "one\ntwo")
        ->newValue(fn (): string => "one\n2")
        ->contextLines(fn (): int => 1)
        ->filename(fn (): string => 'closure.txt');

    $diffFile = $field->getDiffFile();

    expect($diffFile->filename)->toBe('closure.txt')
        ->and($diffFile->additionsCount)->toBe(1)
        ->and($diffFile->deletionsCount)->toBe(1);
});

it('memoizes the computed diff per request', function () {
    $field = diffFieldFixture();

    expect($field->getDiffFile())->toBeInstanceOf(DiffFile::class)
        ->and($field->getDiffFile())->toBe($field->getDiffFile());
});

it('recomputes the diff when reconfigured after a first computation', function () {
    $field = diffFieldFixture();
    $before = $field->getDiffFile();

    $field->newValue("completely\ndifferent");

    expect($field->getDiffFile())->not->toBe($before);
});

it('is never dehydrated into the form payload', function () {
    $field = DiffField::make('changes')
        ->container(Schema::make(new SchemaLivewireComponent));

    expect($field->isDehydrated())->toBeFalse();
});

describe('empty old/new values', function () {
    it('renders a no-changes message instead of a diff when oldValue is empty', function () {
        $html = DiffField::make('changes')
            ->container(Schema::make(new SchemaLivewireComponent))
            ->newValue("one\ntwo")
            ->toHtml();

        expect($html)->toContain('fi-diff-field-empty')
            ->and($html)->toContain('No changes to show.')
            ->and($html)->not->toContain('fi-diff-field-row-addition')
            ->and($html)->not->toContain('<table');
    });

    it('renders a no-changes message instead of a diff when newValue is empty', function () {
        $html = DiffField::make('changes')
            ->container(Schema::make(new SchemaLivewireComponent))
            ->oldValue("one\ntwo")
            ->toHtml();

        expect($html)->toContain('fi-diff-field-empty')
            ->and($html)->not->toContain('fi-diff-field-row-deletion')
            ->and($html)->not->toContain('<table');
    });

    it('renders a no-changes message when neither value is set', function () {
        $html = DiffField::make('changes')
            ->container(Schema::make(new SchemaLivewireComponent))
            ->toHtml();

        expect($html)->toContain('fi-diff-field-empty');
    });

    it('still renders the header with zeroed stats when reporting no changes', function () {
        $html = DiffField::make('changes')
            ->container(Schema::make(new SchemaLivewireComponent))
            ->filename('config/app.php')
            ->newValue("one\ntwo")
            ->toHtml();

        expect($html)->toContain('config/app.php')
            ->and($html)->toContain('+0')
            ->and($html)->toContain('−0')
            ->and($html)->not->toContain('fi-diff-field-stat-square-addition');
    });
});

describe('modal presentation', function () {
    it('is not modal by default, and registers no actions', function () {
        $field = DiffField::make('message')
            ->container(Schema::make(new SchemaLivewireComponent))
            ->oldValue('old')
            ->newValue('new');

        expect($field->isModal())->toBeFalse()
            ->and($field->getDefaultActions())->toBe([]);
    });

    it('renders a compact trigger instead of the panel once modal() is enabled', function () {
        $html = DiffField::make('validation.active_url')
            ->container(Schema::make(new SchemaLivewireComponent))
            ->modal()
            ->filename('validation.active_url')
            ->oldValue('The :attribute test.')
            ->newValue('The :attribute field must be a valid URL.')
            ->toHtml();

        expect($html)->toContain('fi-diff-field-modal-trigger')
            ->and($html)->toContain('validation.active_url')
            ->and($html)->not->toContain('fi-diff-field-table')
            ->and($html)->not->toContain('fi-diff-field-header')
            ->and($html)->toContain('mountAction')
            ->and($html)->toContain('viewDiff');
    });

    it('registers the viewDiff action once modal() is enabled', function () {
        $field = DiffField::make('message')
            ->container(Schema::make(new SchemaLivewireComponent))
            ->modal()
            ->filename('validation.active_url')
            ->oldValue('old')
            ->newValue('new');

        $actions = $field->getDefaultActions();

        expect($actions)->toHaveCount(1)
            ->and($actions[0]->getName())->toBe('viewDiff')
            ->and($actions[0]->getLabel())->toBe('validation.active_url');
    });

    it('has no submit action on the modal until onRollback() is registered', function () {
        $withoutRollback = DiffField::make('message')
            ->container(Schema::make(new SchemaLivewireComponent))
            ->modal();

        expect($withoutRollback->hasRollback())->toBeFalse()
            ->and($withoutRollback->getViewDiffAction()->getModalSubmitAction())->toBeNull();

        $withRollback = DiffField::make('message')
            ->container(Schema::make(new SchemaLivewireComponent))
            ->modal()
            ->onRollback(fn () => null);

        expect($withRollback->hasRollback())->toBeTrue()
            ->and($withRollback->getViewDiffAction()->getModalSubmitAction())->not->toBeNull()
            ->and($withRollback->getViewDiffAction()->getModalSubmitAction()->getLabel())->toBe('Rollback');
    });

    it('resolves the default RollbackManager from the container to build the Rollback button', function () {
        $field = DiffField::make('message')
            ->container(Schema::make(new SchemaLivewireComponent))
            ->modal()
            ->onRollback(fn () => null);

        expect($field->getRollbackActionBuilder())->toBeInstanceOf(RollbackManager::class);
    });

    it('lets the Rollback action builder be swapped per-instance, like AdvancedToggle\'s confirmation builder', function () {
        $customAction = Action::make('rollback')->label('Undo it');

        $builder = new class($customAction) implements BuildsRollbackAction
        {
            public function __construct(private Action $action) {}

            public function build(DiffField $field, Action $action): Action
            {
                return $this->action;
            }
        };

        $field = DiffField::make('message')
            ->container(Schema::make(new SchemaLivewireComponent))
            ->modal()
            ->onRollback(fn () => null)
            ->buildRollbackActionUsing($builder);

        expect($field->getRollbackActionBuilder())->toBe($builder)
            ->and($field->getViewDiffAction()->getModalSubmitAction())->toBe($customAction)
            ->and($field->getViewDiffAction()->getModalSubmitAction()->getLabel())->toBe('Undo it');
    });

    it('rebuilds the viewDiff action after swapping the Rollback builder', function () {
        $field = DiffField::make('message')
            ->container(Schema::make(new SchemaLivewireComponent))
            ->modal()
            ->onRollback(fn () => null);

        $before = $field->getViewDiffAction();

        $field->buildRollbackActionUsing(new RollbackManager);

        expect($field->getViewDiffAction())->not->toBe($before);
    });

    it('invokes the onRollback callback with the old and new values, and never on its own', function () {
        $received = null;

        $field = DiffField::make('message')
            ->container(Schema::make(new SchemaLivewireComponent))
            ->modal()
            ->oldValue('old value')
            ->newValue('new value')
            ->onRollback(function (string $oldValue, string $newValue) use (&$received): void {
                $received = [$oldValue, $newValue];
            });

        expect($received)->toBeNull();

        $field->handleRollback();

        expect($received)->toBe(['old value', 'new value']);
    });

    it('runs the onRollback callback through a real mounted-action click, not just handleRollback() directly', function () {
        $received = null;

        $field = DiffField::make('message')
            ->modal()
            ->oldValue('old value')
            ->newValue('new value')
            ->onRollback(function (string $oldValue, string $newValue) use (&$received): void {
                $received = [$oldValue, $newValue];
            });

        ActionsFormLivewireComponent::$components = [$field];

        Livewire::test(ActionsFormLivewireComponent::class, ['data' => ['message' => null]])
            ->call('mountAction', 'viewDiff', [], ['schemaComponent' => 'form.message'])
            ->call('callMountedAction');

        expect($received)->toBe(['old value', 'new value']);
    });

    it('does nothing when handleRollback() is called without a registered callback', function () {
        $field = DiffField::make('message')
            ->container(Schema::make(new SchemaLivewireComponent))
            ->modal();

        $field->handleRollback();
    })->throwsNoExceptions();

    it('memoizes the word-diff tokens per request', function () {
        $field = DiffField::make('message')
            ->container(Schema::make(new SchemaLivewireComponent))
            ->oldValue('old')
            ->newValue('new');

        expect($field->getWordDiffTokens())->toBe($field->getWordDiffTokens());
    });

    it('recomputes the word-diff tokens when reconfigured', function () {
        $field = DiffField::make('message')
            ->container(Schema::make(new SchemaLivewireComponent))
            ->oldValue('old')
            ->newValue('new');

        $before = $field->getWordDiffTokens();

        $field->newValue('completely different');

        expect($field->getWordDiffTokens())->not->toBe($before);
    });

    it('renders the modal content with Side-by-side boxes and an Inline word-diff view', function () {
        $field = DiffField::make('message')
            ->container(Schema::make(new SchemaLivewireComponent))
            ->modal()
            ->oldValue('The :attribute test.')
            ->newValue('The :attribute field must be a valid URL.');

        $html = $field->getViewDiffAction()->getModalContent()->render();

        expect($html)->toContain('fi-diff-field-modal-toggle')
            ->and($html)->toContain('Side by Side')
            ->and($html)->toContain('Inline')
            ->and($html)->toContain('fi-diff-field-modal-box-old')
            ->and($html)->toContain('fi-diff-field-modal-box-new')
            ->and($html)->toContain('The :attribute test.')
            ->and($html)->toContain('The :attribute field must be a valid URL.')
            ->and($html)->toContain('fi-diff-field-modal-token-deletion')
            ->and($html)->toContain('fi-diff-field-modal-token-addition')
            ->and($html)->toContain('>test.<')
            ->and($html)->toContain('>field must be a valid URL.<');
    });

    it('shows the empty placeholder in the modal when a side is blank', function () {
        $field = DiffField::make('message')
            ->container(Schema::make(new SchemaLivewireComponent))
            ->modal()
            ->newValue('brand new value');

        $html = $field->getViewDiffAction()->getModalContent()->render();

        expect($html)->toContain('fi-diff-field-modal-box-empty')
            ->and($html)->toContain('(empty)')
            ->and($html)->toContain('brand new value');
    });

    it('renders inline word-diff tokens with no whitespace between spans', function () {
        // The Inline paragraph uses `white-space: normal` (not the plain
        // boxes' `pre-wrap`), because word spacing already lives inside each
        // token's own text — so any whitespace the *template* leaves between
        // spans would render as spurious extra gaps or line breaks. Assert
        // the compiled HTML has zero such whitespace between token tags.
        $field = DiffField::make('message')
            ->container(Schema::make(new SchemaLivewireComponent))
            ->modal()
            ->oldValue('The :attribute test.')
            ->newValue('The :attribute field must be a valid URL.');

        $html = $field->getViewDiffAction()->getModalContent()->render();

        preg_match('/<p class="fi-diff-field-modal-inline-content">(.*?)<\/p>/s', $html, $matches);

        expect($matches[1])->toBe(
            '<span class="fi-diff-field-modal-token fi-diff-field-modal-token-context">The :attribute </span>'
            . '<span class="fi-diff-field-modal-token fi-diff-field-modal-token-deletion">test.</span>'
            . '<span class="fi-diff-field-modal-token fi-diff-field-modal-token-addition">field must be a valid URL.</span>',
        );
    });
});
