<?php

declare(strict_types=1);

namespace GlEvents\SyliusAdminSamlPlugin\Security;

use GlEvents\SyliusAdminSamlPlugin\Provider\SamlConfigProvider;
use GlEvents\SyliusAdminSamlPlugin\Provider\SamlUserProvider;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Error;
use OneLogin\Saml2\ValidationError;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class SamlAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly SamlConfigProvider $samlConfigProvider,
        private readonly SamlUserProvider $userProvider,
        private readonly RouterInterface $router,
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'SAML_IDENTIFIER_KEY')]
        private readonly string $samlIdentifierKey,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return 'sylius_admin_saml_acs' === $request->attributes->get('_route');
    }

    /**
     * @throws ValidationError
     * @throws Error
     */
    public function authenticate(Request $request): Passport
    {
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

            throw new AuthenticationException('SAML authentication failed.');
        }

        $attributes = $auth->getAttributes();

        $email = $attributes[$this->samlIdentifierKey][0];

        $user = $this->userProvider->loadUserByEmail($email);

        if (null === $user) {
            $this->logger->info('Trying to login with SAML but user not found', [
                'login_error' => 'user_not_found',
                'saml_failure' => [
                    'email' => $email,
                    'host' => $request->getHost(),
                ],
            ]);

            throw new AuthenticationException('User not found');
        }

        return new SelfValidatingPassport(new UserBadge($email, function () use ($user) {
            return $user;
        }));
    }

    /**
     * @throws Error
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
    {
        $url = $this->router->generate('sylius_admin_dashboard');

        return new RedirectResponse($url);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // On failure, return appropriate response
        return new JsonResponse(['error' => $exception->getMessageKey()], Response::HTTP_UNAUTHORIZED);
    }
}
