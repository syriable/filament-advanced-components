{{--
    DiffField — a GitHub-style unified diff view.

    Every row (including the ones hidden inside collapsed context blocks) is
    rendered server-side; Alpine only flips a per-hunk `expanded` flag, so
    expanding hidden context is instant and never round-trips to Livewire.
    All line/hunk computation happens in DiffGenerator/DiffHunkBuilder — this
    template just walks the resulting DiffFile.
--}}
@php
    $diffFile = $getDiffFile();
    $heading = $diffFile->filename ?? $getLabel() ?? __('filament-advanced-components::diff-field.untitled');
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        {{ $attributes->class(['fi-diff-field']) }}
    >
        <div class="fi-diff-field-header">
            <span class="fi-diff-field-filename">{{ $heading }}</span>

            <span
                class="fi-diff-field-stats"
                aria-label="{{ __('filament-advanced-components::diff-field.stats_label', ['additions' => $diffFile->additionsCount, 'deletions' => $diffFile->deletionsCount]) }}"
            >
                <span class="fi-diff-field-stat-additions">{{ __('filament-advanced-components::diff-field.additions', ['count' => $diffFile->additionsCount]) }}</span>
                <span class="fi-diff-field-stat-deletions">{{ __('filament-advanced-components::diff-field.deletions', ['count' => $diffFile->deletionsCount]) }}</span>

                <span class="fi-diff-field-stat-squares" aria-hidden="true">
                    @foreach ($diffFile->statSquares() as $square)
                        <span class="fi-diff-field-stat-square fi-diff-field-stat-square-{{ $square }}"></span>
                    @endforeach
                </span>
            </span>
        </div>

        @if ($diffFile->hasNoChanges())
            <div class="fi-diff-field-empty">
                {{ __('filament-advanced-components::diff-field.no_changes') }}
            </div>
        @else
            <div class="fi-diff-field-scroll">
                <table class="fi-diff-field-table">
                    @foreach ($diffFile->hunks as $hunk)
                        @if ($hunk->isCollapsed)
                            <tbody
                                x-data="{ expanded: false }"
                                class="fi-diff-field-hunk fi-diff-field-hunk-collapsed"
                            >
                                <tr x-show="! expanded" class="fi-diff-field-expand-row">
                                    <td colspan="4">
                                        <button
                                            type="button"
                                            x-on:click="expanded = true"
                                            class="fi-diff-field-expand-button"
                                        >
                                            <svg class="fi-diff-field-expand-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M10.53 3.47a.75.75 0 0 0-1.06 0L6.22 6.72a.75.75 0 0 0 1.06 1.06L10 5.06l2.72 2.72a.75.75 0 1 0 1.06-1.06l-3.25-3.25Zm-4.31 9.81 3.25 3.25a.75.75 0 0 0 1.06 0l3.25-3.25a.75.75 0 1 0-1.06-1.06L10 14.94l-2.72-2.72a.75.75 0 0 0-1.06 1.06Z" clip-rule="evenodd" />
                                            </svg>

                                            <span>{{ trans_choice('filament-advanced-components::diff-field.expand', $hunk->hiddenCount, ['count' => $hunk->hiddenCount]) }}</span>
                                        </button>
                                    </td>
                                </tr>

                                @foreach ($hunk->lines as $line)
                                    <tr
                                        x-show="expanded"
                                        x-cloak
                                        class="fi-diff-field-row fi-diff-field-row-{{ $line->type->value }}"
                                    >
                                        <td class="fi-diff-field-gutter">{{ $line->oldLineNo }}</td>
                                        <td class="fi-diff-field-gutter">{{ $line->newLineNo }}</td>
                                        <td class="fi-diff-field-prefix">{{ $line->type->prefix() }}</td>
                                        <td class="fi-diff-field-content">{{ $line->content }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        @else
                            <tbody class="fi-diff-field-hunk">
                                @foreach ($hunk->lines as $line)
                                    <tr class="fi-diff-field-row fi-diff-field-row-{{ $line->type->value }}">
                                        <td class="fi-diff-field-gutter">{{ $line->oldLineNo }}</td>
                                        <td class="fi-diff-field-gutter">{{ $line->newLineNo }}</td>
                                        <td class="fi-diff-field-prefix">{{ $line->type->prefix() }}</td>
                                        <td class="fi-diff-field-content">{{ $line->content }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        @endif
                    @endforeach
                </table>
            </div>
        @endif
    </div>
</x-dynamic-component>
