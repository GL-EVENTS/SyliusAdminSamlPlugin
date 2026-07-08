<?php

declare(strict_types=1);

namespace Tests\GlEvents\SamlPlugin\Unit\Provider;

use GlEvents\SyliusAdminSamlPlugin\Provider\SamlConfigProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class SamlConfigProviderTest extends TestCase
{
    public function testItBuildsTheSamlSettingsFromTheCurrentRequest(): void
    {
        $config = $this->createProvider('https://shop.example.com/admin/login/saml')->getConfig();

        self::assertSame('idp-entity-id', $config['idp']['entityId']);
        self::assertSame('https://idp.example.com/sso', $config['idp']['singleSignOnService']['url']);
        self::assertSame('https://idp.example.com/slo', $config['idp']['singleLogoutService']['url']);
        self::assertSame('IDP-CERTIFICATE', $config['idp']['x509cert']);

        self::assertSame('https://shop.example.com', $config['sp']['entityId']);
        self::assertSame(
            'https://shop.example.com/admin/login/saml/acs',
            $config['sp']['assertionConsumerService']['url'],
        );
        self::assertSame(
            'https://shop.example.com/admin/login/saml/logout',
            $config['sp']['singleLogoutService']['url'],
        );
        self::assertSame('SP-PRIVATE-KEY', $config['sp']['privateKey']);
    }

    public function testItDerivesTheServiceProviderUrlsFromTheRequestScheme(): void
    {
        $config = $this->createProvider('http://localhost/admin/login/saml')->getConfig();

        self::assertSame('http://localhost', $config['sp']['entityId']);
        self::assertSame('http://localhost/admin/login/saml/acs', $config['sp']['assertionConsumerService']['url']);
    }

    private function createProvider(string $uri): SamlConfigProvider
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create($uri));

        return new SamlConfigProvider(
            $requestStack,
            'SP-PRIVATE-KEY',
            'idp-entity-id',
            'https://idp.example.com/sso',
            'https://idp.example.com/slo',
            'IDP-CERTIFICATE',
            'admin',
            false,
        );
    }
}
