<?php

namespace Shopery\Bundle\I18nBundle\Routing;

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Generator\Dumper\PhpGeneratorDumper;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Matcher\Dumper\PhpMatcherDumper;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class CachedRouterFactory implements RouterFactory
{
    private $cacheDir;
    private $dirName;

    public function __construct($cacheDir, $dirName)
    {
        $this->cacheDir = $cacheDir;
        $this->dirName = $dirName;
    }

    public function setCacheDir($cacheDir)
    {
        $previous = $this->cacheDir;
        $this->cacheDir = $cacheDir;

        return $previous;
    }

    public function create(RequestContext $context, $locale = null)
    {
        $generator = $this->generator($locale, $context);
        $matcher = $this->matcher($locale, $context);

        return new CachedRouter($generator, $matcher, $context);
    }

    public function dump(RouteCollection $collection, $locale)
    {
        $filesystem = new Filesystem();

        $dumper = new PhpMatcherDumper($collection);
        $className = $this->matcherClassName($locale);
        $filename = $this->matcherFilename($locale);
        $filesystem->dumpFile($filename, $dumper->dump([
            'class' => $className,
            'base_class' => UrlMatcher::class,
        ]));

        $dumper = new PhpGeneratorDumper($collection);
        $className = $this->generatorClassName($locale);
        $filename = $this->generatorFilename($locale);
        $filesystem->dumpFile($filename, $dumper->dump([
            'class' => $className,
            'base_class' => UrlGenerator::class,
        ]));

        $collection->addResource(new FileResource($this->generatorFilename($locale)));
        $collection->addResource(new FileResource($this->matcherFilename($locale)));
    }

    private function generator($locale, $context)
    {
        require_once $this->generatorFilename($locale);
        $className = $this->generatorClassName($locale);

        return new $className($context);
    }

    private function generatorFilename($locale = null)
    {
        $path = $this->cachePath();
        if ($locale) {
            $path .= '/' . $locale;
        }

        return $path . '/generator.php';
    }

    private function generatorClassName($locale = null)
    {
        $className = 'UrlGenerator';
        if ($locale) {
            $className .= 'ForLocale' . ucfirst($locale);
        } else {
            $className .= 'Global';
        }

        return $className;
    }

    private function matcher($locale, $context)
    {
        require_once $this->matcherFilename($locale);
        $className = $this->matcherClassName($locale);

        return new $className($context);
    }

    private function matcherFilename($locale = null)
    {
        $path = $this->cachePath();
        if ($locale) {
            $path .= '/' . $locale;
        }

        return $path . '/matcher.php';
    }

    private function matcherClassName($locale = null)
    {
        $className = 'UrlMatcher';
        if ($locale) {
            $className .= 'ForLocale' . ucfirst($locale);
        } else {
            $className .= 'Global';
        }

        return $className;
    }

    private function cachePath()
    {
        return $this->cacheDir . '/' . $this->dirName;
    }
}
