{{--
    Multi-segment progress bar, shared by MultiProgressColumn (tables) and
    MultiProgressEntry (infolists).

    This template is intentionally "dumb": every percentage, color class,
    tooltip and label is precomputed once per cell by
    HasMultiProgressBar::getProgressData(), so rendering thousands of rows
    stays cheap. Alpine.js is only used for Filament's `x-tooltip` directive,
    and only when a segment actually has a tooltip.
--}}
@php
    use Illuminate\Contracts\Support\Htmlable;
    use Illuminate\Support\Js;

    $data = $getProgressData();
    $segments = $data['segments'];
    $isCompact = $isCompact();
    $placeholder = $getPlaceholder();

    // When the whole cell is already a link/button (a column url()/action()
    // or a table recordUrl/recordAction), a clickable segment cannot be a
    // real <a> nested inside it — the browser would tear the markup apart.
    // In that case segments navigate via script instead.
    $isNested = $isNestedInInteractiveElement();

    $navigationExpression = function (array $segment): string {
        $url = Js::from($segment['url']);

        return $segment['shouldOpenUrlInNewTab'] ? "window.open({$url}, '_blank')" : "window.location.href = {$url}";
    };

    // Sizing is passed to the stylesheet through CSS custom properties, so
    // arbitrary heights, gaps and radii never require extra CSS classes.
    $rootStyles = implode(';', [
        '--fi-ta-mp-height: ' . $getHeight(),
        '--fi-ta-mp-gap: ' . $getGap() . 'px',
        '--fi-ta-mp-radius: ' . $getBorderRadius(),
    ]);

    $tooltipAttribute = function (string|Htmlable|null $tooltip): ?string {
        if (blank($tooltip)) {
            return null;
        }

        return '{
            content: ' .
            Js::from($tooltip instanceof Htmlable ? $tooltip->toHtml() : $tooltip) .
            ',
            theme: $store.theme,
            allowHTML: ' .
            Js::from($tooltip instanceof Htmlable) .
            ',
        }';
    };
@endphp

<div
    {{ $getExtraAttributeBag()->class([
            'fi-multi-progress',
            'fi-multi-progress-compact' => $isCompact,
            'fi-multi-progress-animated' => $isAnimated(),
            'fi-multi-progress-striped' => $isStriped(),
            'fi-multi-progress-gradient' => $hasGradient(),
            'fi-multi-progress-hoverable' => $hasHoverEffect(),
        ])->style([$rootStyles]) }}>
    @if ($data['isEmpty'])
        @if ($hasSkeleton())
            {{-- Loading skeleton: a pulsing placeholder track. --}}
            <div class="fi-multi-progress-row">
                <div class="fi-multi-progress-track fi-multi-progress-skeleton" role="status"
                    aria-label="{{ __('filament-advanced-components::multi-progress.loading') }}"></div>
            </div>
        @else
            {{-- Empty state: the column's configured placeholder, if any. --}}
            <div class="fi-multi-progress-row">
                <div class="fi-multi-progress-track" role="img" aria-label="{{ $data['ariaLabel'] }}"></div>

                @if (filled($placeholder))
                    <span class="fi-multi-progress-placeholder">
                        {{ $placeholder }}
                    </span>
                @endif
            </div>
        @endif
    @else
        <div class="fi-multi-progress-row">
            <div class="fi-multi-progress-track">
                {{--
                    Screen readers get a single, comprehensible summary of the
                    whole bar instead of a soup of unlabeled colored boxes.
                --}}
                <span class="fi-multi-progress-sr-only">
                    {{ $data['ariaLabel'] }}
                </span>

                @foreach ($segments as $segment)
                    @continue($segment['width'] <= 0)

                    @php
                        $segmentClasses = implode(' ', ['fi-multi-progress-segment', ...$segment['color']['classes']]);

                        $segmentStyles = implode(
                            ';',
                            array_filter(['width: ' . $segment['width'] . '%', $segment['color']['styles']]),
                        );

                        $segmentAriaLabel = filled($segment['label'])
                            ? "{$segment['label']}: {$segment['formattedValue']} ({$segment['formattedPercentage']})"
                            : "{$segment['formattedValue']} ({$segment['formattedPercentage']})";

                        $tooltip = $tooltipAttribute($segment['tooltip']);
                    @endphp

                    @if (filled($segment['url']) && $isNested)
                        {{--
                            Clickable segment inside a linked cell: a nested
                            <a> is invalid HTML, so use a focusable role="link"
                            that navigates via script and stops the click from
                            also triggering the surrounding cell link.
                        --}}
                        <div role="link" tabindex="0" class="{{ $segmentClasses }}" style="{{ $segmentStyles }}"
                            aria-label="{{ $segmentAriaLabel }}"
                            x-on:click.stop.prevent="{{ $navigationExpression($segment) }}"
                            x-on:keydown.enter.stop.prevent="{{ $navigationExpression($segment) }}"
                            @if ($tooltip) x-tooltip="{{ $tooltip }}" @endif></div>
                    @elseif (filled($segment['url']))
                        {{-- Clickable segment: a real link, natively focusable. --}}
                        <a {!! \Filament\Support\generate_href_html($segment['url'], $segment['shouldOpenUrlInNewTab'])->toHtml() !!} class="{{ $segmentClasses }}" style="{{ $segmentStyles }}"
                            aria-label="{{ $segmentAriaLabel }}"
                            @if ($tooltip) x-tooltip="{{ $tooltip }}" @endif></a>
                    @elseif ($tooltip)
                        {{--
                            Tooltip-only segment: focusable so keyboard users can
                            trigger the tooltip, and labelled for screen readers
                            (a focusable element must never be aria-hidden).
                        --}}
                        <div class="{{ $segmentClasses }}" style="{{ $segmentStyles }}" role="img"
                            aria-label="{{ $segmentAriaLabel }}" tabindex="0" x-tooltip="{{ $tooltip }}"></div>
                    @else
                        {{-- Purely decorative: the summary above covers it. --}}
                        <div class="{{ $segmentClasses }}" style="{{ $segmentStyles }}" aria-hidden="true"></div>
                    @endif
                @endforeach
            </div>

            @if (
                ($shouldShowPercentage() && filled($data['formattedPercentage']))
                || ($shouldShowTotal() && filled($data['formattedTotal']))
            )
                <span class="fi-multi-progress-value">
                    @if ($shouldShowPercentage() && filled($data['formattedPercentage']))
                        <span @class([
                            'fi-multi-progress-percentage',
                            ...$getPercentageVisibilityClasses(),
                        ])>
                            {{ $data['formattedPercentage'] }}
                        </span>
                    @endif

                    @if ($shouldShowTotal() && filled($data['formattedTotal']))
                        <span @class([
                            'fi-multi-progress-total',
                            ...$getTotalVisibilityClasses(),
                        ])>
                            {{ $data['formattedTotal'] }}
                        </span>
                    @endif
                </span>
            @endif
        </div>

        @if ($shouldShowLegend())
            <ul @class([
                'fi-multi-progress-legend',
                ...$getLegendVisibilityClasses(),
            ])>
                @foreach ($segments as $segment)
                    <li class="fi-multi-progress-legend-item">
                        <span class="fi-multi-progress-legend-dot {{ implode(' ', $segment['color']['classes']) }}"
                            @if (filled($segment['color']['styles'])) style="{{ $segment['color']['styles'] }}" @endif
                            aria-hidden="true"></span>

                        @if (filled($segment['iconHtml']))
                            <span class="fi-multi-progress-legend-icon" aria-hidden="true">
                                {{ $segment['iconHtml'] }}
                            </span>
                        @endif

                        <span class="fi-multi-progress-legend-label">
                            {{ $segment['label'] }}
                        </span>

                        <span class="fi-multi-progress-legend-value">
                            {{ $segment['formattedPercentage'] }}
                        </span>

                        @if (filled($segment['badge']))
                            <span class="fi-multi-progress-legend-badge">
                                {{ $segment['badge'] }}
                            </span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    @endif
</div>
