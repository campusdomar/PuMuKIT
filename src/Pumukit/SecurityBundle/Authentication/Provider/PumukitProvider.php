<?php

namespace Pumukit\SecurityBundle\Authentication\Provider;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Pumukit\SchemaBundle\Services\UserService;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Document\Group;

class PumukitProvider implements AuthenticationProviderInterface
{

    const CAS_CN_KEY = 'CN';
    const CAS_MAIL_KEY = 'MAIL';
    const CAS_GROUP_KEY = 'GROUP';

    private $userProvider;
    private $providerKey;
    private $userChecker;
    private $container;
    private $createUsers;

    public function __construct(UserProviderInterface $userProvider, $providerKey, UserCheckerInterface $userChecker, ContainerInterface $container, $createUsers = true)
    {
        $this->userProvider = $userProvider;
        $this->providerKey = $providerKey;
        $this->userChecker = $userChecker;
        //NOTE: using container instead of tag service to avoid ServiceCircularReferenceException.
        $this->container = $container;
        $this->createUsers = $createUsers;
    }

    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            return;
        }

        if (!$user = $token->getUser()) {
            throw new BadCredentialsException('No pre-authenticated principal found in request.');
        }

        try {
            $user = $this->userProvider->loadUserByUsername($user);
        } catch (UsernameNotFoundException $notFound) {
            if ($this->createUsers) {
                $user = $this->createUser($user);
            } else {
                throw new BadCredentialsException('Bad credentials', 0, $notFound);
            }
        } catch (\Exception $repositoryProblem) {
            $ex = new AuthenticationServiceException($repositoryProblem->getMessage(), 0, $repositoryProblem);
            $ex->setToken($token);
            throw $ex;
        }

        $this->userChecker->checkPreAuth($user);
        $this->userChecker->checkPostAuth($user);

        $authenticatedToken = new PreAuthenticatedToken($user, $token->getCredentials(), $this->providerKey, $user->getRoles());
        $authenticatedToken->setAttributes($token->getAttributes());

        return $authenticatedToken;
    }

    private function createUser($username)
    {
        $userService = $this->container->get('pumukitschema.user');
        $personService = $this->container->get('pumukitschema.person');
        $casService = $this->container->get('pumukit.casservice');

        $casService->forceAuthentication();
        $attributes = $casService->getAttributes();

        $permissionProfileService = $this->container->get('pumukitschema.permissionprofile');
        //TODO create createDefaultUser in UserService.
        if (isset($attributes[self::CAS_CN_KEY])) {
            $username = ($attributes[self::CAS_CN_KEY]);
        }
        $email = '';
        if (isset($attributes[self::CAS_MAIL_KEY]))
            $email = $attributes[self::CAS_MAIL_KEY];

        $defaultPermissionProfile = $permissionProfileService->getDefault();
        if (null == $defaultPermissionProfile) {
            throw new \Exception('Unable to assign a Permission Profile to the new User. There is no default Permission Profile');
        }
        $group = null;
        if (isset($attributes[self::CAS_GROUP_KEY])) {
            $group = $this->loginService->getGroup($attributes[self::CAS_GROUP_KEY], 'cas');
        }
        $origin = 'cas';
        $enabled = true;
        return $this->loginService->createDefaultUser($username, $email, $defaultPermissionProfile, $group, $origin, $enabled);

        throw new AuthenticationServiceException('Not UserService to create a new user');
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof PreAuthenticatedToken;
    }
}
