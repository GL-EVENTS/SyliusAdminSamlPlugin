<?php

declare(strict_types=1);

namespace GlEvents\SyliusAdminSamlPlugin\Controller;

use GlEvents\SyliusAdminSamlPlugin\Provider\SamlConfigProvider;
use GlEvents\SyliusAdminSamlPlugin\Provider\SamlUserProvider;
use GlEvents\SyliusAdminSamlPlugin\Security\SamlAuthenticator;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Error;
use OneLogin\Saml2\ValidationError;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Translation\TranslatableMessage;

final class SamlAcsAction extends AbstractController
{
    public function __construct(
        private readonly SamlConfigProvider $samlConfigProvider,
        private readonly UserAuthenticatorInterface $userAuthenticator,
        private readonly SamlAuthenticator $authenticator,
        private readonly SamlUserProvider $samlUserProvider,
        private readonly LoggerInterface $logger,
        #[Autowire(param: 'gl_events_sylius_admin_saml.identifier_key')]
        private readonly string $samlIdentifierKey,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $this->logger->info('Processing SAML ACS response');

        try {
            $auth = new Auth($this->samlConfigProvider->getConfig());
            $auth->processResponse();
        } catch (Error | ValidationError $e) {
            $this->logger->critical('Unable to process SAML response', [
                'login_error' => 'saml_response_invalid',
                'saml_failure' => [
                    'host' => $request->getHost(),
                    'exception' => $e->getMessage(),
                ],
            ]);
            $this->addFlash('error', new TranslatableMessage('saml_auth.flash.authentication_failed'));

            return $this->redirectToRoute('sylius_admin_login');
        }

        if (!$auth->isAuthenticated()) {
            $this->logger->critical('SAML authentication failed', [
                'login_error' => 'auth_failed',
                'saml_failure' => [
                    'host' => $request->getHost(),
                    'last_error_reason' => $auth->getLastErrorReason(),
                    'last_error_exception' => $auth->getLastErrorException(),
                    'errors' => implode(' ', $auth->getErrors()),
                ],
            ]);
            $this->addFlash('error', new TranslatableMessage('saml_auth.flash.authentication_failed'));

            return $this->redirectToRoute('sylius_admin_login');
        }

        $attributes = $auth->getAttributes();

        if (!isset($attributes[$this->samlIdentifierKey][0])) {
            $this->logger->critical('SAML response is missing the identifier attribute', [
                'login_error' => 'identifier_attribute_missing',
                'saml_failure' => [
                    'identifier_key' => $this->samlIdentifierKey,
                    'host' => $request->getHost(),
                ],
            ]);
            $this->addFlash('error', new TranslatableMessage('saml_auth.flash.authentication_failed'));

            return $this->redirectToRoute('sylius_admin_login');
        }

        $email = $attributes[$this->samlIdentifierKey][0];
        /** @var UserInterface|null $user */
        $user = $this->samlUserProvider->loadUserByEmail($email);

        if (null === $user) {
            $this->logger->info('Trying to login with SAML but user not found', [
                'login_error' => 'user_not_found',
                'saml_failure' => [
                    'email' => $email,
                    'host' => $request->getHost(),
                ],
            ]);
            $this->addFlash('error', new TranslatableMessage('saml_auth.flash.not_authorized'));

            return $this->redirectToRoute('sylius_admin_login');
        }

        try {
            $response = $this->userAuthenticator->authenticateUser(
                $user,
                $this->authenticator,
                $request,
            );
        } catch (\Exception $e) {
            $this->logger->error('Error during SAML authentication for ' . $email, ['exception' => $e]);
            $this->addFlash('error', new TranslatableMessage('saml_auth.flash.authentication_failed'));

            return $this->redirectToRoute('sylius_admin_login');
        }

        return $response ?? $this->redirectToRoute('sylius_admin_dashboard');
    }
}
