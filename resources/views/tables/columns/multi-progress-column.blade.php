{{--
    Multi-segment progress bar column.

    This template is intentionally "dumb": every percentage, color class,
    tooltip and label is precomputed once per cell by
    MultiProgressColumn::getProgressData(), so rendering thousands of rows
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

    // Sizing is passed to the stylesheet through CSS custom properties, so
    // arbitrary heights, gaps and radii never require extra CSS classes.
    $rootStyles = implode(';', [
        '--fi-ta-mp-height: ' . $getHeight(),
        '--fi-ta-mp-gap: ' . $getGap() . 'px',
        '--fi-ta-mp-radius: ' . $getBorderRadius(),
    ]);

    $tooltipAttribute = function (string | Htmlable | null $tooltip): ?string {
        if (blank($tooltip)) {
            return null;
        }

        return '{
            content: ' . Js::from($tooltip instanceof Htmlable ? $tooltip->toHtml() : $tooltip) . ',
            theme: $store.theme,
            allowHTML: ' . Js::from($tooltip instanceof Htmlable) . ',
        }';
    };
@endphp

<div
    {{
        $getExtraAttributeBag()
            ->class([
                'fi-ta-multi-progress',
                'fi-ta-multi-progress-compact' => $isCompact,
                'fi-ta-multi-progress-animated' => $isAnimated(),
                'fi-ta-multi-progress-striped' => $isStriped(),
                'fi-ta-multi-progress-gradient' => $hasGradient(),
                'fi-ta-multi-progress-hoverable' => $hasHoverEffect(),
            ])
            ->style([$rootStyles])
    }}
>
    @if ($data['isEmpty'])
        @if ($hasSkeleton())
            {{-- Loading skeleton: a pulsing placeholder track. --}}
            <div class="fi-ta-multi-progress-row">
                <div
                    class="fi-ta-multi-progress-track fi-ta-multi-progress-skeleton"
                    role="status"
                    aria-label="{{ __('filament-advanced-components::multi-progress-column.loading') }}"
                ></div>
            </div>
        @else
            {{-- Empty state: the column's configured placeholder, if any. --}}
            <div class="fi-ta-multi-progress-row">
                <div
                    class="fi-ta-multi-progress-track"
                    role="img"
                    aria-label="{{ $data['ariaLabel'] }}"
                ></div>

                @if (filled($placeholder))
                    <span class="fi-ta-multi-progress-placeholder">
                        {{ $placeholder }}
                    </span>
                @endif
            </div>
        @endif
    @else
        <div class="fi-ta-multi-progress-row">
            <div class="fi-ta-multi-progress-track">
                {{--
                    Screen readers get a single, comprehensible summary of the
                    whole bar instead of a soup of unlabeled colored boxes.
                --}}
                <span class="fi-ta-multi-progress-sr-only">
                    {{ $data['ariaLabel'] }}
                </span>

                @foreach ($segments as $segment)
                    @continue($segment['width'] <= 0)

                    @php
                        $segmentClasses = implode(' ', [
                            'fi-ta-multi-progress-segment',
                            ...$segment['color']['classes'],
                        ]);

                        $segmentStyles = implode(';', array_filter([
                            'width: ' . $segment['width'] . '%',
                            $segment['color']['styles'],
                        ]));

                        $segmentAriaLabel = filled($segment['label'])
                            ? "{$segment['label']}: {$segment['formattedValue']} ({$segment['formattedPercentage']})"
                            : "{$segment['formattedValue']} ({$segment['formattedPercentage']})";

                        $tooltip = $tooltipAttribute($segment['tooltip']);
                    @endphp

                    @if (filled($segment['url']))
                        {{-- Clickable segment: a real link, natively focusable. --}}
                        <a
                            {!! \Filament\Support\generate_href_html($segment['url'], $segment['shouldOpenUrlInNewTab'])->toHtml() !!}
                            class="{{ $segmentClasses }}"
                            style="{{ $segmentStyles }}"
                            aria-label="{{ $segmentAriaLabel }}"
                            @if ($tooltip)
                                x-tooltip="{{ $tooltip }}"
                            @endif
                        ></a>
                    @elseif ($tooltip)
                        {{--
                            Tooltip-only segment: focusable so keyboard users can
                            trigger the tooltip, and labelled for screen readers
                            (a focusable element must never be aria-hidden).
                        --}}
                        <div
                            class="{{ $segmentClasses }}"
                            style="{{ $segmentStyles }}"
                            role="img"
                            aria-label="{{ $segmentAriaLabel }}"
                            tabindex="0"
                            x-tooltip="{{ $tooltip }}"
                        ></div>
                    @else
                        {{-- Purely decorative: the summary above covers it. --}}
                        <div
                            class="{{ $segmentClasses }}"
                            style="{{ $segmentStyles }}"
                            aria-hidden="true"
                        ></div>
                    @endif
                @endforeach
            </div>

            @if (filled($data['formattedPercentage']) || filled($data['formattedTotal']))
                <span class="fi-ta-multi-progress-value">
                    @if (filled($data['formattedPercentage']))
                        {{ $data['formattedPercentage'] }}
                    @endif

                    @if (filled($data['formattedTotal']))
                        <span class="fi-ta-multi-progress-total">
                            {{ filled($data['formattedPercentage']) ? '· ' : '' }}{{ $data['formattedTotal'] }}
                        </span>
                    @endif
                </span>
            @endif
        </div>

        @if ($shouldShowLegend())
            <ul class="fi-ta-multi-progress-legend">
                @foreach ($segments as $segment)
                    <li class="fi-ta-multi-progress-legend-item">
                        <span
                            class="fi-ta-multi-progress-legend-dot {{ implode(' ', $segment['color']['classes']) }}"
                            @if (filled($segment['color']['styles']))
                                style="{{ $segment['color']['styles'] }}"
                            @endif
                            aria-hidden="true"
                        ></span>

                        @if (filled($segment['iconHtml']))
                            <span class="fi-ta-multi-progress-legend-icon" aria-hidden="true">
                                {{ $segment['iconHtml'] }}
                            </span>
                        @endif

                        <span class="fi-ta-multi-progress-legend-label">
                            {{ $segment['label'] }}
                        </span>

                        <span class="fi-ta-multi-progress-legend-value">
                            {{ $segment['formattedPercentage'] }}
                        </span>

                        @if (filled($segment['badge']))
                            <span class="fi-ta-multi-progress-legend-badge">
                                {{ $segment['badge'] }}
                            </span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    @endif
</div>
