<?php

declare(strict_types=1);

namespace GlEvents\SyliusAdminSamlPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('gl_events_sylius_admin_saml');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('idp')
                    ->info('Identity Provider settings.')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('entity_id')->defaultValue('')->end()
                        ->scalarNode('sso_url')->defaultValue('')->end()
                        ->scalarNode('slo_url')->defaultValue('')->end()
                        ->scalarNode('certificate')->defaultValue('')->end()
                    ->end()
                ->end()
                ->arrayNode('sp')
                    ->info('Service Provider settings.')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('private_key')->defaultValue('')->end()
                    ->end()
                ->end()
                ->scalarNode('identifier_key')
                    ->info('SAML attribute holding the admin user email.')
                    ->defaultValue('')
                ->end()
                ->booleanNode('proxy_vars')
                    ->info('Enable when running behind an SSL-terminating reverse proxy.')
                    ->defaultFalse()
                ->end()
                ->booleanNode('admin_login')
                    ->info('Display the traditional Sylius admin login form.')
                    ->defaultTrue()
                ->end()
                ->booleanNode('sso_login')
                    ->info('Display the SSO login button.')
                    ->defaultTrue()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
