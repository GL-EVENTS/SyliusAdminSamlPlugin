<?php

declare(strict_types=1);

namespace GlEvents\SyliusAdminSamlPlugin\Twig;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class SamlLoginGlobalsExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        #[Autowire(param: 'gl_events_sylius_admin_saml.admin_login')]
        private readonly bool $adminLogin,
        #[Autowire(param: 'gl_events_sylius_admin_saml.sso_login')]
        private readonly bool $ssoLogin,
    ) {
    }

    /**
     * @return array<string, bool>
     */
    public function getGlobals(): array
    {
        return [
            'default_admin_login_enabled' => $this->adminLogin,
            'sso_login' => $this->ssoLogin,
        ];
    }
}
