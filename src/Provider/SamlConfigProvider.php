<?php

declare(strict_types=1);

namespace GlEvents\SamlPlugin\Provider;

use OneLogin\Saml2\Error;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SamlConfigProvider
{
    public function __construct(
        private  readonly RequestStack $requestStack,
        private  readonly string $spPrivateKey,
        private  readonly string $idpEntityId,
        private  readonly string $idpSsoUrl,
        private  readonly string $idpSlourl,
        private  readonly string $idpCert,
    ) {
    }

    /**
     * @throws Error
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
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
                    'url' => $schemeAndHost . '/admin/login/saml/acs',
                ],
                'singleLogoutService' => [
                    'url' => $schemeAndHost . '/admin/login/saml/logout',
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
