<?php

declare(strict_types=1);

namespace GlEvents\SyliusAdminSamlPlugin\Provider;

use OneLogin\Saml2\Error;
use OneLogin\Saml2\Utils;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SamlConfigProvider
{
    public function __construct(
        private readonly RequestStack $requestStack,
        #[Autowire(param: 'gl_events_sylius_admin_saml.sp.private_key')]
        private readonly string $spPrivateKey,
        #[Autowire(param: 'gl_events_sylius_admin_saml.idp.entity_id')]
        private readonly string $idpEntityId,
        #[Autowire(param: 'gl_events_sylius_admin_saml.idp.sso_url')]
        private readonly string $idpSsoUrl,
        #[Autowire(param: 'gl_events_sylius_admin_saml.idp.slo_url')]
        private readonly string $idpSlourl,
        #[Autowire(param: 'gl_events_sylius_admin_saml.idp.certificate')]
        private readonly string $idpCert,
        #[Autowire(param: 'sylius_admin.path_name')]
        private readonly string $syliusAdminPathName,
        #[Autowire(param: 'gl_events_sylius_admin_saml.proxy_vars')]
        private readonly bool $proxyVars = false,
    ) {
    }

    /**
     * @return array<string, mixed>
     *
     * @throws Error
     */
    public function getConfig(): array
    {
        Utils::setProxyVars($this->proxyVars);

        [$scheme, $host] = $this->getSPEntityId();

        $schemeAndHost = sprintf('%s://%s', $scheme, $host);

        return [
            'idp' => [
                'entityId' => $this->idpEntityId,
                'singleSignOnService' => ['url' => $this->idpSsoUrl],
                'singleLogoutService' => ['url' => $this->idpSlourl],
                'x509cert' => $this->idpCert,
            ],
            'sp' => [
                'entityId' => $schemeAndHost,
                'assertionConsumerService' => [
                    'url' => $schemeAndHost . '/' . $this->syliusAdminPathName . '/login/saml/acs',
                ],
                'singleLogoutService' => [
                    'url' => $schemeAndHost . '/' . $this->syliusAdminPathName . '/login/saml/logout',
                ],
                'privateKey' => $this->spPrivateKey,
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function getSPEntityId(): array
    {
        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();

        $scheme = $request->getScheme();
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'];
        }

        $host = $request->getHost();
        if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
        }

        return [$scheme, $host];
    }
}
