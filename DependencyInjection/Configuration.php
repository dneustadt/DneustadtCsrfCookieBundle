<?php

declare(strict_types=1);

namespace Dneustadt\CsrfCookieBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\HttpFoundation\Cookie;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('dneustadt_csrf_cookie');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->booleanNode('enable')->defaultTrue()->end()
            ->scalarNode('id')->cannotBeEmpty()->defaultValue('csrf')->end()
            ->scalarNode('name')->cannotBeEmpty()->defaultValue('XSRF-TOKEN')->end()
            ->integerNode('expire')->defaultValue(0)->end()
            ->scalarNode('path')->cannotBeEmpty()->defaultValue('/')->end()
            ->scalarNode('domain')->defaultNull()->end()
            ->booleanNode('httpOnly')->defaultFalse()->end()
            ->booleanNode('secure')->defaultFalse()->end()
            ->scalarNode('header')->cannotBeEmpty()->defaultValue('X-XSRF-TOKEN')->end()
            ->scalarNode('sameSite')->cannotBeEmpty()->defaultValue(Cookie::SAMESITE_LAX)->end()
            ->end();

        return $treeBuilder;
    }
}
