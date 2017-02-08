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

    /**
     * @var array
     */
    private $userAttributeLabels;

    public function __construct(TokenStorageInterface $tokenStorage, array $userAttributeLabels)
    {
        $this->tokenStorage = $tokenStorage;
        $this->userAttributeLabels = $userAttributeLabels;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('username', [$this, 'getUserName']),
            new Twig_SimpleFunction('render_extra_user_attribute', [$this, 'renderExtraUserAttribute']),
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
     * @param string $attribute
     * @param string $value
     *
     * @return string
     */
    public function renderExtraUserAttribute($attribute, $value)
    {
        if (isset($this->userAttributeLabels[$attribute])) {
            $attribute = $this->userAttributeLabels[$attribute];
        }

        return sprintf('%s: %s', $attribute, $value);
    }

    /**
     * @return User
     */
    private function getUser()
    {
        return $this->tokenStorage->getToken()->getUser();
    }
}
