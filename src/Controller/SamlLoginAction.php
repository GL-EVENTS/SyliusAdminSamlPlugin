<?php

declare(strict_types=1);

namespace GlEvents\SyliusAdminSamlPlugin\Controller;

use GlEvents\SyliusAdminSamlPlugin\Provider\SamlConfigProvider;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Error;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatableMessage;

final class SamlLoginAction extends AbstractController
{
    public function __construct(
        private readonly SamlConfigProvider $samlConfigProvider,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(): Response
    {
        $this->logger->info('Starting SAML login');

        try {
            $auth = new Auth($this->samlConfigProvider->getConfig());
            $ssoUrl = $auth->login(returnTo: null, stay: true);
        } catch (Error $e) {
            $this->logger->error('Unable to start SAML login', ['exception' => $e]);
            $this->addFlash('error', new TranslatableMessage('saml_auth.flash.sso_unavailable'));

            return $this->redirectToRoute('sylius_admin_login');
        }

        return new RedirectResponse($ssoUrl);
    }
}
