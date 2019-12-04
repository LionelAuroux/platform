<?php

namespace Oro\Bundle\DistributionBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('oro_distribution');
        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('entry_point')
                    ->beforeNormalization()
                        ->ifNull()
                        ->then(
                            function () {
                                return 'install.php';
                            }
                        )
                    ->end()
                ->end()
                ->scalarNode('composer_cache_home')
                    ->defaultValue('%kernel.project_dir%/var/cache/composer')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
