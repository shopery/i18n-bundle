<?php

namespace Shopery\Bundle\I18nBundle\Routing\RouteStrategy;

class PrefixedPathStrategy implements RouteStrategy
{
    private $butDefault;

    public function __construct($butDefault = true)
    {
        $this->butDefault = $butDefault;
    }

    public function isLocalized($path, array $locales)
    {
        $detectedPrefix = $this->detectPrefix($path);

        return array_key_exists($detectedPrefix, $this->$locales);
    }

    public function withLocale($path, $locale, array $locales)
    {
        if ($this->butDefault) {
            foreach ($locales as $defaultLocale) {
                if ($locale === $defaultLocale) {
                    return $path;
                }

                break;
            }
        }

        return '/' . $locale . $path;
    }

    public function matchingLocales($path, array $locales)
    {
        $possibleLocaleInPath = $this->detectPrefix($path);

        if (array_search($possibleLocaleInPath, $locales) !== false) {
            return [$possibleLocaleInPath];
        }

        return [reset($locales)];
    }

    private function detectPrefix($path)
    {
        $path = trim($path, '/');
        if (preg_match('~^([^/]{2})(?:$|/)~', $path, $matches)) {
            return $matches[1];
        }
    }
}
