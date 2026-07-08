<?php

declare(strict_types=1);

namespace GlEvents\SyliusAdminSamlPlugin\DependencyInjection;

use Sylius\Bundle\CoreBundle\DependencyInjection\PrependDoctrineMigrationsTrait;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class GlEventsSyliusAdminSamlExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    use PrependDoctrineMigrationsTrait;

    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var array{
         *     idp: array{entity_id: ?string, sso_url: ?string, slo_url: ?string, certificate: ?string},
         *     sp: array{private_key: ?string},
         *     identifier_key: ?string,
         *     proxy_vars: bool,
         *     admin_login: bool,
         *     sso_login: bool,
         * } $config
         */
        $config = $this->processConfiguration(new Configuration(), $configs);

        // Cast to string so an explicit `null` (e.g. `identifier_key: ~`) degrades to '' instead of
        // breaking the string-typed service arguments. Empty settings are handled gracefully at
        // runtime (Auth throws / the identifier attribute is missing), not at container compile time.
        $container->setParameter('gl_events_sylius_admin_saml.idp.entity_id', (string) $config['idp']['entity_id']);
        $container->setParameter('gl_events_sylius_admin_saml.idp.sso_url', (string) $config['idp']['sso_url']);
        $container->setParameter('gl_events_sylius_admin_saml.idp.slo_url', (string) $config['idp']['slo_url']);
        $container->setParameter('gl_events_sylius_admin_saml.idp.certificate', (string) $config['idp']['certificate']);
        $container->setParameter('gl_events_sylius_admin_saml.sp.private_key', (string) $config['sp']['private_key']);
        $container->setParameter('gl_events_sylius_admin_saml.identifier_key', (string) $config['identifier_key']);
        $container->setParameter('gl_events_sylius_admin_saml.proxy_vars', $config['proxy_vars']);
        $container->setParameter('gl_events_sylius_admin_saml.admin_login', $config['admin_login']);
        $container->setParameter('gl_events_sylius_admin_saml.sso_login', $config['sso_login']);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config'),
        );

        $loader->load('services.yaml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->prependDoctrineMigrations($container);
    }

    protected function getMigrationsNamespace(): string
    {
        return 'DoctrineMigrations';
    }

    protected function getMigrationsDirectory(): string
    {
        return '@GlEventsSyliusAdminSamlPlugin/src/Migrations';
    }

    protected function getNamespacesOfMigrationsExecutedBefore(): array
    {
        return [
            'Sylius\Bundle\CoreBundle\Migrations',
        ];
    }
}
