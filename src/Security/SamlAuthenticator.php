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
        #[Autowire(param: 'gl_events_sylius_admin_saml.identifier_key')]
        private readonly string $samlIdentifierKey,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return 'glevents_sylius_saml_plugin_admin_acs' === $request->attributes->get('_route');
    }

    public function authenticate(Request $request): Passport
    {
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

            throw new AuthenticationException('SAML authentication failed.', previous: $e);
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

            throw new AuthenticationException('SAML authentication failed.');
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

            throw new AuthenticationException('SAML authentication failed.');
        }

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
