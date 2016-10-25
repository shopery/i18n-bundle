<?php

namespace Shopery\Bundle\I18nBundle\Routing;

class CachedRouterFactory
{
    private $cacheDir;

    public function __construct($cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    public function create($locale = null)
    {
        $dirName = $this->cacheDir;
        if ($locale) {
            $dirName .= '/' . $locale;
        }

        if (!is_dir($dirName)) {
            mkdir($dirName, 0777, true);
        }

        return new CachedRouter($dirName . '/routing.phpser', $locale);
    }
}
