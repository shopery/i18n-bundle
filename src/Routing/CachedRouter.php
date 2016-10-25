<?php

namespace Shopery\Bundle\I18nBundle\Routing;

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Routing\Generator\ConfigurableRequirementsInterface;
use Symfony\Component\Routing\Generator\Dumper\PhpGeneratorDumper;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\Dumper\PhpMatcherDumper;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class CachedRouter implements RouterInterface
{
    private $locale;
    private $context;
    private $collection;
    private $matcher;
    private $generator;
    private $cachedPathName;

    /**
     * @param string|null $locale
     */
    public function __construct(
        $cachedPathName,
        $locale = null
    ) {
        $this->cachedPathName = $cachedPathName;
        $this->locale = $locale;
    }

    public function setContext(RequestContext $context)
    {
        $this->context = $context;

        if (null !== $this->matcher) {
            $this->getMatcher()->setContext($context);
        }

        if (null !== $this->generator) {
            $this->getGenerator()->setContext($context);
        }
    }

    public function getContext()
    {
        return $this->context;
    }

    public function initialize(RouteCollection $collection)
    {
        $this->collection = $collection;

        $this->writeMatcher();
        $this->writeGenerator();

        file_put_contents($this->cachedPathName, serialize($collection));

        return [
            new FileResource($this->cachedPathName),
            new FileResource($this->matcherFilename()),
            new FileResource($this->generatorFilename()),
        ];
    }

    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        return $this->getGenerator()->generate($name, $parameters, $referenceType);
    }

    public function getRouteCollection()
    {
        if (null === $this->collection) {
            $this->collection = unserialize(
                file_get_contents($this->cachedPathName)
            );
        }

        return $this->collection;
    }

    public function match($pathinfo)
    {
        return $this->getMatcher()->match($pathinfo);
    }

    /**
     * Gets the UrlMatcher instance associated with this Router.
     *
     * @return UrlMatcherInterface A UrlMatcherInterface instance
     */
    public function getMatcher()
    {
        if (null === $this->matcher) {
            $this->matcher = $this->readMatcher();
        }

        return $this->matcher;
    }

    /**
     * Gets the UrlGenerator instance associated with this Router.
     *
     * @return UrlGeneratorInterface A UrlGeneratorInterface instance
     */
    public function getGenerator()
    {
        if (null === $this->generator) {
            $this->generator = $this->readGenerator();
        }

        return $this->generator;
    }

    private function writeMatcher()
    {
        $dumper = new PhpMatcherDumper($this->getRouteCollection());
        $className = $this->matcherClassName();
        $filename = $this->matcherFilename();
        file_put_contents(
            $filename,
            $dumper->dump([
                'class' => $className,
                'base_class' => UrlMatcher::class,
            ])
        );
    }

    private function readMatcher()
    {
        $className = $this->matcherClassName();
        $filename = $this->matcherFilename();

        require_once $filename;

        return new $className($this->context);
    }

    private function matcherClassName()
    {
        return 'UrlMatcher' . ($this->locale ? ucfirst($this->locale) : 'Global');
    }

    private function matcherFilename()
    {
        return dirname($this->cachedPathName) . '/matcher.php';
    }

    private function writeGenerator()
    {
        $dumper = new PhpGeneratorDumper($this->getRouteCollection());
        $className = $this->generatorClassName();
        $filename = $this->generatorFilename();
        file_put_contents(
            $filename,
            $dumper->dump([
                'class' => $className,
                'base_class' => UrlGenerator::class,
            ])
        );
    }

    private function readGenerator()
    {
        $className = $this->generatorClassName();
        $filename = $this->generatorFilename();

        require_once $filename;

        $generator = new $className($this->context);

        if ($generator instanceof ConfigurableRequirementsInterface) {
            $generator->setStrictRequirements(true);
        }

        return $generator;
    }

    private function generatorClassName()
    {
        return 'UrlGenerator' . ($this->locale ? ucfirst($this->locale) : 'Global');
    }

    private function generatorFilename()
    {
        return dirname($this->cachedPathName) . '/generator.php';
    }
}
