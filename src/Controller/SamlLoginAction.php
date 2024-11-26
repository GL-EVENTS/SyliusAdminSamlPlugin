<?php

declare(strict_types=1);

namespace GlEvents\SyliusAdminSamlPlugin\Controller;

use GlEvents\SyliusAdminSamlPlugin\Provider\SamlConfigProvider;
use OneLogin\Saml2\Auth;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

final class SamlLoginAction extends AbstractController
{
    public function __construct(
        private  readonly SamlConfigProvider $samlConfigProvider,
        private  readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(Request $request): void
    {
        $this->logger->info('Starting SAML login for with Azure');

        $config = $this->samlConfigProvider->getConfig();
        $auth = new Auth($config);
        $auth->login();
    }
}
