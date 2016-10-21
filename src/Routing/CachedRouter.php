<?php

namespace Shopery\Bundle\I18nBundle\Routing;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class CachedRouter implements RouterInterface
{
    private $options;
    private $context;
    private $locale;

    private $collection;
    private $matcher;
    private $generator;

    /**
     * @param string|null $locale
     */
    public function __construct(array $options = array(), RequestContext $context = null, $locale = null)
    {
        $this->options = $options;
        $this->context = $context;
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

    public function initializeRouteCollection(RouteCollection $collection)
    {
        //TODO
        $this->collection = $collection;
    }

    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        //TODO:: If wrong cache in generator... Crash...???
        $this->getGenerator()->generate($name, $parameters, $referenceType);
    }

    public function getRouteCollection()
    {
        if (null === $this->collection) {
            //TODO:: What to do? Q.Q Should never happen
        }

        return $this->collection;
    }

    public function match($pathinfo)
    {
        //TODO:: If wrong cache in matcher... Crash...???
        $this->getMatcher()->match($pathinfo);
    }

    /**
     * Gets the UrlMatcher instance associated with this Router.
     *
     * @return UrlMatcherInterface A UrlMatcherInterface instance
     */
    public function getMatcher()
    {
        if (null !== $this->matcher) {
            return $this->matcher;
        }

        if (null === $this->options['cache_dir'] || null === $this->options['matcher_cache_class']) {
            $this->matcher = new $this->options['matcher_class']($this->getRouteCollection(), $this->context);
            if (method_exists($this->matcher, 'addExpressionLanguageProvider')) {
                foreach ($this->expressionLanguageProviders as $provider) {
                    $this->matcher->addExpressionLanguageProvider($provider);
                }
            }

            return $this->matcher;
        }

        $class = $this->options['matcher_cache_class'];
        $baseClass = $this->options['matcher_base_class'];
        $expressionLanguageProviders = $this->expressionLanguageProviders;
        $that = $this; // required for PHP 5.3 where "$this" cannot be use()d in anonymous functions. Change in Symfony 3.0.

        $cache = $this->getConfigCacheFactory()->cache($this->options['cache_dir'].'/'.$class.'.php',
            function (ConfigCacheInterface $cache) use ($that, $class, $baseClass, $expressionLanguageProviders) {
                $dumper = $that->getMatcherDumperInstance();
                if (method_exists($dumper, 'addExpressionLanguageProvider')) {
                    foreach ($expressionLanguageProviders as $provider) {
                        $dumper->addExpressionLanguageProvider($provider);
                    }
                }

                $options = array(
                    'class' => $class,
                    'base_class' => $baseClass,
                );

                $cache->write($dumper->dump($options), $that->getRouteCollection()->getResources());
            }
        );

        require_once $cache->getPath();

        return $this->matcher = new $class($this->context);
    }

    /**
     * Gets the UrlGenerator instance associated with this Router.
     *
     * @return UrlGeneratorInterface A UrlGeneratorInterface instance
     */
    public function getGenerator()
    {
        if (null !== $this->generator) {
            return $this->generator;
        }

        if (null === $this->options['cache_dir'] || null === $this->options['generator_cache_class']) {
            $this->generator = new $this->options['generator_class']($this->getRouteCollection(), $this->context, $this->logger);
        } else {
            $class = $this->options['generator_cache_class'];
            $baseClass = $this->options['generator_base_class'];
            $that = $this; // required for PHP 5.3 where "$this" cannot be use()d in anonymous functions. Change in Symfony 3.0.
            $cache = $this->getConfigCacheFactory()->cache($this->options['cache_dir'].'/'.$class.'.php',
                function (ConfigCacheInterface $cache) use ($that, $class, $baseClass) {
                    $dumper = $that->getGeneratorDumperInstance();

                    $options = array(
                        'class' => $class,
                        'base_class' => $baseClass,
                    );

                    $cache->write($dumper->dump($options), $that->getRouteCollection()->getResources());
                }
            );

            require_once $cache->getPath();

            $this->generator = new $class($this->context, $this->logger);
        }

        if ($this->generator instanceof ConfigurableRequirementsInterface) {
            $this->generator->setStrictRequirements($this->options['strict_requirements']);
        }

        return $this->generator;
    }
}
