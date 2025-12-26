<?php
/**
 * PHP SDK for Lingo.dev
 *
 * @category Localization
 * @package  Lingodotdev\Sdk
 * @author   Lingo.dev Team <hi@lingo.dev>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://lingo.dev
 */

namespace LingoDotDev\Sdk;

/**
 * Options for text and object translation.
 *
 * @category Localization
 * @package  Lingodotdev\Sdk
 * @author   Lingo.dev Team <hi@lingo.dev>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://lingo.dev
 */
class TranslationOptions
{
    /**
     * Target language code (e.g., 'es', 'fr')
     * Required field.
     */
    public string $targetLocale;

    /**
     * Source language code or null for auto-detection
     * Example: 'en', 'es', or null
     */
    public ?string $sourceLocale = null;

    /**
     * Enable fast mode - trades translation quality for speed
     * Default: false (quality mode)
     */
    public bool $fast = false;

    /**
     * Context data or glossary terms to guide translation
     * Can include domain-specific terminology or reference translations
     *
     * @var array<string, mixed>|null
     */
    public ?array $reference = null;


    /**
     * Create a fluent builder for translation options.
     *
     * @param string $targetLocale Target language code
     */
    public static function create(string $targetLocale): self
    {
        $instance = new self();
        $instance->targetLocale = $targetLocale;
        return $instance;
    }

    /**
     * Set source locale.
     *
     * @param string|null $locale Source language code or null for auto-detection
     */
    public function from(?string $locale): self
    {
        $this->sourceLocale = $locale;
        return $this;
    }

    /**
     * Enable fast translation mode.
     */
    public function withFastMode(): self
    {
        $this->fast = true;
        return $this;
    }

    /**
     * Add reference data for context.
     *
     * @param array<string, mixed> $reference Context or glossary data
     */
    public function withReference(array $reference): self
    {
        $this->reference = $reference;
        return $this;
    }
}