<?php

namespace Shopery\Bundle\I18nBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class I18nBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(
            new DependencyInjection\CompilerPass\RouterSetupFromFramework()
        );
    }

    protected function createContainerExtension()
    {
        return new DependencyInjection\I18nExtension();
    }
}
