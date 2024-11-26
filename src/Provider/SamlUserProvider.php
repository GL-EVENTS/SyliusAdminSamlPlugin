<?php

declare(strict_types=1);

namespace GlEvents\SamlPlugin\Provider;

use Sylius\Component\User\Repository\UserRepositoryInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SamlUserProvider
{
    public function __construct(
        private UserRepositoryInterface $adminUserRepository,
    ) {
    }

    public function loadUserByEmail(string $identifier): ?UserInterface
    {
        /** @var UserInterface|null $user */
        $user = $this->adminUserRepository->findOneBy(['email' => $identifier]);

        return $user;
    }
}
