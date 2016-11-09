<?php

namespace Shopery\Bundle\I18nBundle\Routing\RouteStrategy;

interface RouteStrategy
{
    /**
     * Used once for generating the available routes' cache.
     *
     * @param string $path Path without taking in account the locale
     * @param string $locale Locale the path must be adapted to
     * @param array $locales Available locales in the application sorted by priority. First one is the default
     *
     * @return string
     */
    public function withLocale($path, $locale, array $locales);

    /**
     * Used on execution to match a given route with the existing ones.
     *
     * @param string $path Route to match
     * @param string[] $locales Available locales which may match the route sorted by priority.
     *                          First one is the default
     * @return string|null  - string: Locale the path may belong to. I.e., the path either matches this locale
     *                      or is NOT localized, but for sure will NOT match any other locale
     *                      - null: Path is for sure NOT localized
     */
    public function matchingLocale($path, array $locales);
}
