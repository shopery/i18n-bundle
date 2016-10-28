<?php

namespace Shopery\Bundle\I18nBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class I18nBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(
            new DependencyInjection\CompilerPass\OverrideDefaultRouter()
        );
    }

    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension =  new DependencyInjection\I18nExtension();
        }

        return $this->extension;
    }
}
