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

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    protected function setUp()
    {
        $this->twigLoader = new Twig_Loader_Array();
        $this->twig = new Twig_Environment($this->twigLoader);

        $this->securityToken = new UsernamePasswordToken('username', 'password', 'provider');

        $this->tokenStorage = new TokenStorage();
        $this->tokenStorage->setToken($this->securityToken);
    }

    /**
     * @test
     */
    public function shouldReturnDisplayNameIfPresent()
    {
        $this->securityToken->setUser($this->createUser('Display Name', 'UID'));

        $extension = new UserExtension($this->tokenStorage, []);
        $this->twig->addExtension($extension);
        $this->twigLoader->setTemplate('template.html.twig', '{{ username() }}');

        $this->assertSame('Display Name', $this->twig->render('template.html.twig'));
    }

    /**
     * @test
     */
    public function shouldReturnUidIfDisplayNameIsEmpty()
    {
        $this->securityToken->setUser($this->createUser('', 'UID'));

        $extension = new UserExtension($this->tokenStorage, []);
        $this->twig->addExtension($extension);
        $this->twigLoader->setTemplate('template.html.twig', '{{ username() }}');

        $this->assertSame('UID', $this->twig->render('template.html.twig'));
    }

    /**
     * @test
     */
    public function shouldRenderUserAttribute()
    {
        $extension = new UserExtension($this->tokenStorage, ['foo' => 'Foo label']);
        $this->twig->addExtension($extension);
        $this->twigLoader->setTemplate('template.html.twig', "{{ render_extra_user_attribute('foo', 'value') }}");

        $this->assertSame('Foo label: value', $this->twig->render('template.html.twig'));
    }

    /**
     * @test
     */
    public function shouldReturnDisplayNameOfUser()
    {
        $extension = new UserExtension($this->tokenStorage, []);
        $this->twig->addExtension($extension);
        $this->twigLoader->setTemplate('template.html.twig', '{{ user|display_name }}');

        $user = new User(null, null, null, null, 'DisplayName');

        $this->assertSame('DisplayName', $this->twig->render('template.html.twig', ['user' => $user]));
    }

    /**
     * @test
     */
    public function shouldReturnFullNameIfDisplayNameIsEmpty()
    {
        $extension = new UserExtension($this->tokenStorage, []);
        $this->twig->addExtension($extension);
        $this->twigLoader->setTemplate('template.html.twig', '{{ user|display_name }}');

        $user = new User(null, null, 'John', 'Smith');

        $this->assertSame('John Smith', $this->twig->render('template.html.twig', ['user' => $user]));
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
