<?php

namespace CommerceGuys\Intl\Currency;

use CommerceGuys\Intl\LocaleResolverTrait;
use Symfony\Component\Yaml\Parser;

/**
 * Manages currencies based on YAML definitions.
 */
class DefaultCurrencyManager implements CurrencyManagerInterface
{
    use LocaleResolverTrait;

    /**
     * Base currency definitions.
     *
     * Contains data common to all locales, such as the currency numeric
     * code, number of fraction digits.
     *
     * @var array
     */
    protected $baseDefinitions = array();

    /**
     * Per-locale currency definitions.
     *
     * @var array
     */
    protected $definitions = array();

    /**
     * The yaml parser.
     *
     * @var \Symfony\Component\Yaml\Parser
     */
    protected $parser;

    public function __construct()
    {
        $this->parser = new Parser();
        $this->definitionPath = __DIR__ . '/../../resources/currency/';
        $this->baseDefinitions = $this->parser->parse(file_get_contents($this->definitionPath . 'base.yml'));
    }

    /**
     * {@inheritdoc}
     */
    public function get($currencyCode, $locale = 'en', $fallbackLocale = null)
    {
        $locale = $this->resolveLocale($locale, $fallbackLocale);
        $definitions = $this->loadDefinitions($locale);
        if (!isset($definitions[$currencyCode])) {
            throw new UnknownCurrencyException($currencyCode);
        }

        return $this->createCurrencyFromDefinition($definitions[$currencyCode], $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function getAll($locale = 'en', $fallbackLocale = null)
    {
        $locale = $this->resolveLocale($locale, $fallbackLocale);
        $definitions = $this->loadDefinitions($locale);
        $currencies = array();
        foreach ($definitions as $currencyCode => $definition) {
            $currencies[$currencyCode] = $this->createCurrencyFromDefinition($definition, $locale);
        }

        return $currencies;
    }

    /**
     * Loads the currency definitions for the provided locale.
     *
     * @param string $locale
     *   The desired locale.
     *
     * @return array
     */
    protected function loadDefinitions($locale)
    {
        if (!isset($this->definitions[$locale])) {
            $filename = $this->definitionPath . $locale . '.yml';
            $this->definitions[$locale] = $this->parser->parse(file_get_contents($filename));
            // Merge-in base definitions.
            foreach ($this->definitions[$locale] as $currencyCode => $definition) {
                $this->definitions[$locale][$currencyCode] += $this->baseDefinitions[$currencyCode];
            }
        }

        return $this->definitions[$locale];
    }

    /**
     * Creates a currency object from the provided definition.
     *
     * @param array $definition The currency definition.
     * @param string $locale The locale of the currency definition.
     *
     * @return \CommerceGuys\Intl\Currency\Currency
     */
    protected function createCurrencyFromDefinition(array $definition, $locale)
    {
        $definition += array(
            'fraction_digits' => 2,
        );

        $currency = new Currency();
        $currency->setCurrencyCode($definition['code']);
        $currency->setName($definition['name']);
        $currency->setNumericCode($definition['numeric_code']);
        $currency->setFractionDigits($definition['fraction_digits']);
        $currency->setSymbol($definition['symbol']);
        $currency->setLocale($locale);

        return $currency;
    }
}