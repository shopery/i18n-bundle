<?php
/**
 * This file is part of shopery/shopery
 *
 * Copyright (c) 2016 Shopery.com
 */

namespace Shopery\Bundle\I18nBundle\Routing\RouteStrategy;


use Symfony\Component\Routing\Exception\RouteNotFoundException;

class PrefixedPath implements RouteStrategy
{
    const ALL_LOCALES = 'all_locales';
    const DEFAULT_LOCALE = 'default_locale';
    const DEFAULT_IS_PREFIXED = 'default_is_prefixed';

    /** @var string[] */
    private $prefixedLocales;
    /** @var string|null */
    private $defaultLocale;
    /** @var bool */
    private $defaultIsPrefixed;

    /**
     * @param bool $defaultIsPrefixed
     * @param string $defaultLocale
     */
    public function __construct(array $options)
    {
        $allLocales = $this->getOption($options, self::ALL_LOCALES, []);
        $this->defaultLocale = $this->getOption($options, self::DEFAULT_LOCALE, null);
        $this->defaultIsPrefixed = $this->getOption($options, self::DEFAULT_IS_PREFIXED, false);

        $this->prefixedLocales = ($this->defaultLocaleSetWithoutPrefix())
            ? array_diff($allLocales, [$this->defaultLocale]) //Remove if exists
            : array_merge([$this->defaultLocale], $allLocales); //Ensure it exists
    }

    public function pathWithLocale($path, $locale)
    {
        if (!in_array($locale, $this->allLocales())) {
            throw new RouteNotFoundException(sprintf(
                "Cannot generate route for locale %s, available ones are:%s",
                $locale,
                '"'.implode('","', $this->allLocales()).'""'
            ));
        }

        return ($locale === $this->defaultLocale && $this->defaultLocaleSetWithoutPrefix())
            ? $path
            : '/'.$locale.$path;
    }

    public function pathMustBeLocalized($path)
    {
        $possibleLocaleInPath = $this->firstHierarchyLevelOfPath($path);

        return array_key_exists($possibleLocaleInPath, $this->prefixedLocales);
    }

    public function pathMustBeGlobal($path)
    {
        return $this->defaultIsPrefixed
            ? !$this->pathMustBeLocalized($path)
            : false;
    }

    /**
     * @param string $path
     * @return string[]
     */
    public function localesWhichMayMatchPath($path)
    {
        $possibleLocaleInPath = $this->firstHierarchyLevelOfPath($path);

        if (array_key_exists($possibleLocaleInPath, $this->prefixedLocales)) {
            return [$possibleLocaleInPath];
        }
        if ($this->defaultLocaleSetWithoutPrefix()) {
            return [$this->defaultLocale];
        }

        return [];
    }

    /**
     * @param string $name
     * @param mixed|null $default
     * @return mixed|null
     */
    private function getOption(array $options, $name, $default = null)
    {
        return array_key_exists($name, $options) ? $options[$name] : $default;
    }

    /**
     * @return bool
     */
    private function defaultLocaleSetWithoutPrefix()
    {
        return !$this->defaultIsPrefixed && $this->defaultLocale !== null;
    }

    /**
     * @param $path
     * @return array
     */
    private function firstHierarchyLevelOfPath($path, $prefix = '')
    {
        if (!empty($prefix) && strpos($path, $prefix) === 0) {
            $path = substr($path, strlen($prefix));
        }

        $pathHierarchy = explode('/', ltrim($path, '/'), 2);

        return count($pathHierarchy) > 0 ? $pathHierarchy[0] : null;
    }

    /**
     * @return string[]
     */
    public function allLocales()
    {
        return $this->defaultIsPrefixed
            ? $this->prefixedLocales
            : array_merge([$this->defaultLocale], $this->prefixedLocales);
    }
}
