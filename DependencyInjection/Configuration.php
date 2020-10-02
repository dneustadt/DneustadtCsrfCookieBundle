<?php

declare(strict_types=1);

namespace Dneustadt\CsrfCookieBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('dneustadt_csrf_cookie');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->scalarNode('id')->cannotBeEmpty()->defaultValue('csrf')->end()
            ->scalarNode('name')->cannotBeEmpty()->defaultValue('XSRF-TOKEN')->end()
            ->integerNode('expire')->defaultValue(0)->end()
            ->scalarNode('path')->cannotBeEmpty()->defaultValue('/')->end()
            ->scalarNode('domain')->cannotBeEmpty()->defaultValue(null)->end()
            ->booleanNode('secure')->defaultFalse()->end()
            ->scalarNode('header')->cannotBeEmpty()->defaultValue('X-XSRF-TOKEN')->end()
            ->end();

        return $treeBuilder;
    }
}
