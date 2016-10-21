<?php

namespace Shopery\Bundle\I18nBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class I18nBundle extends Bundle
{
    protected function createContainerExtension()
    {
        return new DependencyInjection\I18nExtension();
    }
}
