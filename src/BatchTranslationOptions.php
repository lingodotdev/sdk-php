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
 * Options for batch text translation to multiple languages.
 *
 * @category Localization
 * @package  Lingodotdev\Sdk
 * @author   Lingo.dev Team <hi@lingo.dev>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://lingo.dev
 */
class BatchTranslationOptions
{
    /**
     * Source language code (e.g., 'en')
     * Required field.
     */
    public string $sourceLocale;

    /**
     * Array of target language codes (e.g., ['es', 'fr', 'de'])
     * Required field.
     *
     * @var string[]
     */
    public array $targetLocales;

    /**
     * Enable fast mode - trades translation quality for speed
     * Default: false (quality mode)
     */
    public bool $fast = false;


    /**
     * Create a fluent builder for batch translation options.
     *
     * @param string $sourceLocale Source language code
     */
    public static function create(string $sourceLocale): self
    {
        $instance = new self();
        $instance->sourceLocale = $sourceLocale;
        $instance->targetLocales = [];
        return $instance;
    }

    /**
     * Set target locales.
     *
     * @param string[] $locales Target language codes
     */
    public function to(array $locales): self
    {
        $this->targetLocales = $locales;
        return $this;
    }

    /**
     * Add a single target locale.
     *
     * @param string $locale Target language code
     */
    public function addTarget(string $locale): self
    {
        $this->targetLocales[] = $locale;
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
}