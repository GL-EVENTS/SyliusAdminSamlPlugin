<?php

declare(strict_types=1);

namespace Tests\GlEvents\SamlPlugin\Unit\DependencyInjection;

use GlEvents\SyliusAdminSamlPlugin\DependencyInjection\GlEventsSyliusAdminSamlExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class GlEventsSyliusAdminSamlExtensionTest extends TestCase
{
    /** @param array<string, mixed> $config */
    #[DataProvider('provideSpEntityIdConfigs')]
    public function testItExposesTheSpEntityIdAsAStringParameter(array $config, string $expected): void
    {
        $container = new ContainerBuilder();

        (new GlEventsSyliusAdminSamlExtension())->load([$config], $container);

        self::assertSame($expected, $container->getParameter('gl_events_sylius_admin_saml.sp.entity_id'));
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function provideSpEntityIdConfigs(): iterable
    {
        yield 'configured' => [['sp' => ['entity_id' => 'https://preprod.example.com']], 'https://preprod.example.com'];
        yield 'omitted' => [[], ''];
        yield 'null' => [['sp' => ['entity_id' => null]], ''];
    }
}
