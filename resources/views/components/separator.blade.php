{{--
    A purely decorative divider. Every value here — label HTML, icon HTML,
    CSS classes, inline styles — was already resolved once by
    SeparatorRenderer, so this template only assembles markup; it never
    escapes, evaluates, or formats anything itself.
--}}
<div
    {{
        $separator->extraAttributes
            ->class([
                'fi-separator',
                'fi-separator-horizontal' => ! $separator->isVertical(),
                'fi-separator-vertical' => $separator->isVertical(),
                'fi-separator-colored' => $separator->isColored(),
                $separator->variantClass,
                $separator->alignmentClass(),
            ])
            ->style([$separator->rootStyles()])
    }}
    role="separator"
    aria-orientation="{{ $separator->orientation->value }}"
>
    <span class="fi-separator-line" aria-hidden="true"></span>

    @if ($separator->hasContent())
        <span class="fi-separator-content">
            @if ($separator->hasIcon() && $separator->isIconBeforeLabel())
                <span class="fi-separator-icon">{!! $separator->iconHtml !!}</span>
            @endif

            @if ($separator->hasLabel())
                <span class="fi-separator-label">{!! $separator->labelHtml !!}</span>
            @endif

            @if ($separator->hasIcon() && ! $separator->isIconBeforeLabel())
                <span class="fi-separator-icon">{!! $separator->iconHtml !!}</span>
            @endif
        </span>

        <span class="fi-separator-line" aria-hidden="true"></span>
    @endif
</div>
