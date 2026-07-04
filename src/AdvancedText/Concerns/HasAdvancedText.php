<?php

declare(strict_types=1);

namespace Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Concerns;

use Closure;
use Filament\Support\Enums\IconSize;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\ComponentAttributeBag;
use Syriable\Filament\Plugins\AdvancedComponents\AdvancedText\Support\ImageRenderer;

use function Filament\Support\generate_icon_html;

/**
 * The complete AdvancedText behavior, shared between the table column and
 * the infolist entry. Filament's `TextColumn` and `TextEntry` expose
 * identical `formatState()`, `getUrl()`, `getTooltip()`, and
 * `toEmbeddedHtml()` extension points, so both components layer the same
 * overrides on top of their respective parent.
 *
 * The using class must implement {@see getAffixIconHtmlComponent()} to name
 * the Filament CSS component its affix icons are colored against.
 */
trait HasAdvancedText
{
    use HasAffixes;
    use HasCharacterCount;
    use HasSmartLinks;
    use HasTextMask;
    use HasTypography;

    /**
     * @var array<Closure>
     */
    protected array $htmlDecorators = [];

    protected bool | Closure $hasFullStateTooltip = false;

    /**
     * The Filament CSS component class that affix icons are colored against.
     *
     * @return class-string
     */
    abstract protected function getAffixIconHtmlComponent(): string;

    /**
     * Register a decorator that may transform the fully rendered cell HTML.
     * Decorators run last, in registration order, and receive the current
     * `$html` (plus the usual `$record` / `$state` injections):
     *
     * ```php
     * ->decorateHtmlUsing(fn (string $html): string => "<div class=\"highlight\">{$html}</div>")
     * ```
     */
    public function decorateHtmlUsing(Closure $decorator): static
    {
        $this->htmlDecorators[] = $decorator;

        return $this;
    }

    /**
     * @return array<Closure>
     */
    public function getHtmlDecorators(): array
    {
        return $this->htmlDecorators;
    }

    /**
     * Show the full, untruncated state as the tooltip — handy next to
     * `limit()` or `words()`. An explicit `tooltip()` always wins, and a
     * masked component never reveals its state this way.
     */
    public function fullStateTooltip(bool | Closure $condition = true): static
    {
        $this->hasFullStateTooltip = $condition;

        return $this;
    }

    public function getTooltip(mixed $state = null): string | Htmlable | null
    {
        $tooltip = parent::getTooltip($state);

        if (filled($tooltip)) {
            return $tooltip;
        }

        if (! $this->evaluate($this->hasFullStateTooltip)) {
            return null;
        }

        if ($this->isMasked() || ! is_scalar($state)) {
            return null;
        }

        return (string) $state;
    }

    /**
     * Masking is applied to the formatted state, so it composes with
     * `formatStateUsing()`, `limit()`, dates, money, and every other native
     * formatter — and feeds the copyable fallback, keeping the clipboard
     * safe by default. HTML states are reduced to plain text before masking,
     * since a partially masked HTML string could leak markup.
     */
    public function formatState(mixed $state): mixed
    {
        $formattedState = parent::formatState($state);

        if (! $this->isMasked()) {
            return $formattedState;
        }

        if ($formattedState instanceof Htmlable) {
            $formattedState = trim(strip_tags($formattedState->toHtml()));
        }

        return $this->applyTextMask((string) $formattedState);
    }

    /**
     * Contact links (`mailable()`, `callable()`, `whatsappable()`) are
     * generated from the component's own state, but an explicit `url()`
     * always wins. Masked state never emits a generated link — the raw
     * value would be readable in the `href`.
     */
    public function getUrl(mixed $state = null): ?string
    {
        if (func_num_args() !== 1) {
            return parent::getUrl();
        }

        $url = parent::getUrl($state);

        if (filled($url)) {
            return $url;
        }

        if ($this->url !== null) {
            return null;
        }

        if ($this->isMasked() || ! $this->hasSmartLink()) {
            return null;
        }

        return $this->getSmartLinkUrl($state);
    }

    /**
     * Applies the advanced decorations to the natively rendered markup. The
     * native markup is wrapped — never rewritten — and only when an advanced
     * feature needs it, so unconfigured components keep the native output
     * byte-for-byte. The column runs this after `toEmbeddedHtml()`; the
     * entry runs it just before the entry wrapper (label, hint, …) so the
     * decorations hug the content.
     */
    protected function decorateAdvancedTextHtml(string $html): string
    {
        $typographyClasses = $this->getTypographyClasses();
        $hasAffixes = $this->hasAffixes();
        $hasCharacterCount = $this->hasCharacterCount();

        if ($hasAffixes || $hasCharacterCount || ($typographyClasses !== [])) {
            $html = $this->wrapAdvancedTextHtml($html, $typographyClasses, $hasAffixes, $hasCharacterCount);
        }

        foreach ($this->htmlDecorators as $decorator) {
            $html = (string) $this->evaluate($decorator, [
                'html' => $html,
            ]);
        }

        return $html;
    }

    /**
     * Wraps the native markup with affixes, typography classes, and the
     * character count. Override in a subclass to change the wrapper markup.
     *
     * @param  array<string>  $typographyClasses
     */
    protected function wrapAdvancedTextHtml(string $html, array $typographyClasses, bool $hasAffixes, bool $hasCharacterCount): string
    {
        if ((! $hasAffixes) && (! $hasCharacterCount)) {
            return '<div class="' . e(implode(' ', $typographyClasses)) . '">' . $html . '</div>';
        }

        $classes = ['fi-adv-text-affixed', ...$typographyClasses];

        $prefixHtml = '';
        $suffixHtml = '';

        if ($hasAffixes) {
            $imageRenderer = $this->getImageRenderer();
            $imageSize = $this->getImageSize();
            $imageBorderRadius = $this->getImageBorderRadius();
            $imageAlt = $this->getImageAlt();

            if (filled($prefixImageUrl = $this->getPrefixImageUrl())) {
                $prefixHtml .= $imageRenderer->render($prefixImageUrl, $imageSize, $imageBorderRadius, $imageAlt)->toHtml();
            }

            $prefixHtml .= $this->generateAffixIconHtml($this->getPrefixIcon(), $this->getPrefixIconColor());
            $suffixHtml .= $this->generateAffixIconHtml($this->getSuffixIcon(), $this->getSuffixIconColor());

            if (filled($suffixImageUrl = $this->getSuffixImageUrl())) {
                $suffixHtml .= $imageRenderer->render($suffixImageUrl, $imageSize, $imageBorderRadius, $imageAlt)->toHtml();
            }
        }

        $characterCountHtml = $hasCharacterCount
            ? $this->generateCharacterCountHtml()
            : '';

        return '<div class="' . e(implode(' ', $classes)) . '">'
            . $prefixHtml
            . '<div class="fi-adv-text-content">' . $html . '</div>'
            . $suffixHtml
            . $characterCountHtml
            . '</div>';
    }

    /**
     * The renderer used for `prefixImage()` / `suffixImage()`. Override to
     * customize the image markup globally for a subclass.
     */
    protected function getImageRenderer(): ImageRenderer
    {
        return app(ImageRenderer::class);
    }

    /**
     * @param  string | \BackedEnum | null  $icon
     * @param  string | array<int | string, string | int> | null  $color
     */
    protected function generateAffixIconHtml(mixed $icon, mixed $color): string
    {
        if (blank($icon)) {
            return '';
        }

        return generate_icon_html(
            $icon,
            attributes: (new ComponentAttributeBag)->color($this->getAffixIconHtmlComponent(), $color),
            size: IconSize::Small,
        )?->toHtml() ?? '';
    }

    protected function generateCharacterCountHtml(): string
    {
        $count = $this->getCharacterCount($this->getState());
        $limit = $this->getCharacterCountLimit();

        $indicator = ($limit !== null) ? "{$count} / {$limit}" : (string) $count;

        $classes = 'fi-adv-text-character-count';

        if (($limit !== null) && ($count > $limit)) {
            $classes .= ' fi-adv-text-character-count-exceeded';
        }

        return '<span class="' . $classes . '">' . e($indicator) . '</span>';
    }
}
