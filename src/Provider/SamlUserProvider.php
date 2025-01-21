<?php

declare(strict_types=1);

namespace GlEvents\SyliusAdminSamlPlugin\Provider;

use Sylius\Component\User\Model\User;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<UserInterface>
 */
class SamlUserProvider implements UserProviderInterface
{
    /** @phpstan-ignore-next-line * */
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

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        if ($this->loadUserByEmail($identifier) !== null) {
            return $this->loadUserByEmail($identifier);
        }

        throw new UserNotFoundException(sprintf(
            'mail "%s" does not exist.',
            $identifier,
        ));
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }
        $getter = 'getEmail';
        if (method_exists($user, $getter)) {
            /** @phpstan-ignore-next-line  */
            $value = $user->$getter();
            if (null !== $value && $value !== '') {
                return $this->loadUserByIdentifier($value);
            }
        }

        throw new UserNotFoundException('User could not be refreshed.');
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }
}
