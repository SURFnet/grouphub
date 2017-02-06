<?php

namespace AppBundle\Twig;

use AppBundle\Model\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig_Extension;
use Twig_SimpleFunction;

final class UserExtension extends Twig_Extension
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('username', [$this, 'getUserName']),
        ];
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        $user = $this->getUser();

        if (empty($user->getDisplayName())) {
            return $user->getLoginName();
        }

        return $user->getDisplayName();
    }

    /**
     * @return User
     */
    private function getUser()
    {
        return $this->tokenStorage->getToken()->getUser();
    }
}
