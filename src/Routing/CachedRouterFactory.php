<?php

namespace Shopery\Bundle\I18nBundle\Routing;

class CachedRouterFactory
{
    private $cacheDir;

    public function __construct($cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    public function create($locale)
    {
        $dirName = $this->cacheDir . '/' . 'cached-router';
        if ($locale) {
            $dirName .= '-' . $locale;
        }

        if (!is_dir($dirName)) {
            mkdir($dirName, 0777, true);
        }

        return new CachedRouter($dirName . '/serialized', $locale);
    }
}
