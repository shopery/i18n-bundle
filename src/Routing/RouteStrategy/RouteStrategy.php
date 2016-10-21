<?php
/**
 * This file is part of shopery/shopery
 *
 * Copyright (c) 2016 Shopery.com
 */

namespace Shopery\Bundle\I18nBundle\Routing\RouteStrategy;


interface RouteStrategy
{
    public function __construct(array $options);

    public function pathWithLocale($path);

    /**
     * @param string $path
     * @return bool
     */
    public function pathMustBeLocalized($path);
    /**
     * @param string $path
     * @return bool
     */
    public function pathMustBeGlobal($path);

    /**
     * @param string $path
     * @return string[]
     */
    public function localesWhichMayMatchPath($path);

    /**
     * @return string[]
     */
    public function allLocales();
}
