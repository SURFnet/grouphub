<?php

namespace Tests\AppBundle\Twig;

use AppBundle\Model\User;
use AppBundle\Twig\UserExtension;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Twig_Environment;
use Twig_Loader_Array;

class UserExtensionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Twig_Loader_Array
     */
    private $twigLoader;

    /**
     * @var Twig_Environment
     */
    private $twig;

    /**
     * @var TokenInterface
     */
    private $securityToken;

    protected function setUp()
    {
        $this->twigLoader = new Twig_Loader_Array();
        $this->twig = new Twig_Environment($this->twigLoader);

        $this->securityToken = new UsernamePasswordToken('username', 'password', 'provider');

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($this->securityToken);

        $extension = new UserExtension($tokenStorage);
        $this->twig->addExtension($extension);
    }

    /**
     * @test
     */
    public function shouldReturnDisplayNameIfPresent()
    {
        $this->securityToken->setUser($this->createUser('Display Name', 'UID'));

        $this->twigLoader->setTemplate('template.html.twig', '{{ username() }}');

        $this->assertSame('Display Name', $this->twig->render('template.html.twig'));
    }

    /**
     * @test
     */
    public function shouldReturnUidIfDisplayNameIsEmpty()
    {
        $this->securityToken->setUser($this->createUser('', 'UID'));

        $this->twigLoader->setTemplate('template.html.twig', '{{ username() }}');

        $this->assertSame('UID', $this->twig->render('template.html.twig'));
    }

    /**
     * @param string $displayName
     * @param string $loginName
     *
     * @return User
     */
    private function createUser($displayName, $loginName)
    {
        return new User(null, '', '', '', $displayName, $loginName);
    }
}
