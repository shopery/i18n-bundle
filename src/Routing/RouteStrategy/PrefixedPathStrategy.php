<?php

namespace Shopery\Bundle\I18nBundle\Routing\RouteStrategy;

class PrefixedPathStrategy implements RouteStrategy
{
    protected $butDefault;

    public function __construct($butDefault = true)
    {
        $this->butDefault = $butDefault;
    }

    public function withLocale($path, $locale, array $locales)
    {
        return $this->localeHasPrefix($locale, $locales)
            ? '/'.$locale.$path
            : $path;
    }

    /**
     * @param string $locale
     * @param array $locales
     * @return bool
     */
    protected function localeHasPrefix($locale, array $locales)
    {
        if ($this->butDefault) {
            foreach ($locales as $defaultLocale) {
                if ($locale === $defaultLocale) {
                    return false;
                }

                break;
            }
        }

        return true;
    }

    public function matchingLocale($path, array $locales)
    {
        $possibleLocaleInPath = $this->detectPrefix($path);

        if ($possibleLocaleInPath !== null
            && array_search($possibleLocaleInPath, $locales) !== false
        ) {
            return $possibleLocaleInPath;
        }

        return $this->butDefault ? reset($locales) : null;
    }

    /**
     * @param string $path
     * @return string|null
     */
    protected function detectPrefix($path)
    {
        $path = trim($path, '/');
        $regexLanguageDialect = '~^(?P<language>[a-z]{2})(\_(?P<dialect>[a-z]{2}))?(?:$|/)~';

        if (preg_match($regexLanguageDialect, $path, $matches)) {
            $dialect = isset($matches['dialect']) ? '_'.$matches['dialect'] : '';

            return $matches['language'].$dialect;
        }

        return null;
    }
}
