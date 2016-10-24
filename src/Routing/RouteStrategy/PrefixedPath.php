<?php

namespace Shopery\Bundle\I18nBundle\Routing\RouteStrategy;

use Symfony\Component\Routing\Exception\RouteNotFoundException;

class PrefixedPath implements RouteStrategy
{
    /** @var string|null */
    private $defaultLocale;
    /** @var bool */
    private $defaultIsPrefixed;
    /** @var string[] */
    private $prefixedLocales;

    /**
     * @param array $allLocales
     * @param string $defaultLocale
     * @param bool $defaultIsPrefixed
     */
    public function __construct(array $allLocales = [], $defaultLocale = null, $defaultIsPrefixed = false)
    {
        $this->defaultLocale = $defaultLocale;
        $this->defaultIsPrefixed = $defaultIsPrefixed;

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

        if (array_search($possibleLocaleInPath, $this->prefixedLocales) !== false) {
            return [$possibleLocaleInPath];
        }

        if ($this->defaultLocaleSetWithoutPrefix()) {
            return [$this->defaultLocale];
        }

        return [];
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
