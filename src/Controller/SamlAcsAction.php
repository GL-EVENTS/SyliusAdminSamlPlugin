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

final class SamlAcsAction extends AbstractController
{
    public function __construct(
        private readonly SamlConfigProvider $samlConfigProvider,
        private readonly UserAuthenticatorInterface $userAuthenticator,
        private readonly SamlAuthenticator $authenticator,
        private readonly SamlUserProvider $samlUserProvider,
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'SAML_IDENTIFIER_KEY')]
        private readonly string $samlIdentifierKey,
    ) {
    }

    /**
     * @throws Error
     * @throws ValidationError
     */
    public function __invoke(Request $request): null|Response
    {
        $this->logger->info('Processing SAML ACS for Azure');

        $auth = new Auth($this->samlConfigProvider->getConfig());
        $auth->processResponse();

        if (!$auth->isAuthenticated()) {
            $this->logger->critical('SAML authentication failed for Azure', [
                'login_error' => 'auth_failed_azure',
                'saml_failure' => [
                    'host' => $request->getHost(),
                    'last_error_reason' => $auth->getLastErrorReason(),
                    'last_error_exception' => $auth->getLastErrorException(),
                    'errors' => implode(' ', $auth->getErrors()),
                ],
            ]);
            $this->addFlash('error', 'SAML authentication failed');

            return $this->redirectToRoute('sylius_admin_login');
        }

        $attributes = $auth->getAttributes();

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
            $this->addFlash('error', 'You are not authorized to access this application');

            return $this->redirectToRoute('sylius_admin_login');
        }

        try {
            return $this->userAuthenticator->authenticateUser(
                $user,
                $this->authenticator,
                $request,
            );
        } catch (\Exception $e) {
            $this->logger->error('Error during SAML authentication with ' . $email . ' for Azure, error: ' . $e->getMessage());

            return new Response('Authentication exception occurred', Response::HTTP_UNAUTHORIZED);
        }
    }
}
