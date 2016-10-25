<?php

namespace Shopery\Bundle\I18nBundle\Routing\RouteStrategy;

interface RouteStrategy
{
    /**
     * @param string $path
     * @param string $locale
     * @param array $locales
     *
     * @return string
     */
    public function withLocale($path, $locale, array $locales);

    /**
     * @param string $path
     *
     * @return bool
     */
    public function isLocalized($path, array $locales);

    /**
     * @param string $path
     *
     * @return string[]
     */
    public function matchingLocales($path, array $locales);
}
