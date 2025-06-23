<?php

namespace App\Tests\Unit\Security;

use App\Repository\UserRepository;
use App\Security\LoginFormAuthenticator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class LoginFormAuthenticatorTest extends TestCase
{
    // Vérifie l'extraction des crédentials
    public function testAuthenticate(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $userRepository = $this->createMock(UserRepository::class);
        $authenticator = new LoginFormAuthenticator($urlGenerator, $userRepository);

        $request = new Request([], [], [], [], [], [], null);
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);
        $request->request->set('username', 'john');
        $request->request->set('password', 'secret');
        $request->request->set('_csrf_token', 'token123');
        $request->attributes->set('_payload', $request->request);

        $passport = $authenticator->authenticate($request);
        $this->assertInstanceOf(Passport::class, $passport);
        $this->assertEquals('john', $session->get(SecurityRequestAttributes::LAST_USERNAME));

        /** @var UserBadge $userBadge */
        $userBadge = $passport->getBadge(UserBadge::class);
        $this->assertInstanceOf(UserBadge::class, $userBadge);
        $this->assertSame('john', $userBadge->getUserIdentifier());

        // 🔁 CORRECTION ICI
        $passwordCredentials = null;
        foreach ($passport->getBadges() as $badge) {
            if ($badge instanceof PasswordCredentials) {
                $passwordCredentials = $badge;
                break;
            }
        }

        $this->assertInstanceOf(PasswordCredentials::class, $passwordCredentials);
        $this->assertSame('secret', $passwordCredentials->getPassword());

        /** @var CsrfTokenBadge $csrfBadge */
        $csrfBadge = $passport->getBadge(CsrfTokenBadge::class);
        $this->assertInstanceOf(CsrfTokenBadge::class, $csrfBadge);
        $this->assertSame('token123', $csrfBadge->getCsrfToken());
    }

    // Vérifie la redirection vers target path
    public function testOnAuthenticationSuccessWithTargetPath(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $userRepository = $this->createMock(UserRepository::class);
        $authenticator = new LoginFormAuthenticator($urlGenerator, $userRepository);

        $session = new Session(new MockArraySessionStorage());
        $session->set('_security.main.target_path', '/dashboard');

        $request = new Request();
        $request->setSession($session);

        $token = $this->createMock(TokenInterface::class);
        $response = $authenticator->onAuthenticationSuccess($request, $token, 'main');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/dashboard', $response->getTargetUrl());
    }

    // Vérifie la redirection vers page la home
    public function testOnAuthenticationSuccessWithoutTargetPath(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->with('app_home')->willReturn('/home');

        $userRepository = $this->createMock(UserRepository::class);
        $authenticator = new LoginFormAuthenticator($urlGenerator, $userRepository);

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));

        $token = $this->createMock(TokenInterface::class);
        $response = $authenticator->onAuthenticationSuccess($request, $token, 'main');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/home', $response->getTargetUrl());
    }

    // Vérifie la Génération de l'URL de login

    /**
     * @throws ReflectionException
     */
    public function testGetLoginUrl(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->once())
            ->method('generate')
            ->with('app_login')
            ->willReturn('/login');

        $userRepository = $this->createMock(UserRepository::class);
        $authenticator = new LoginFormAuthenticator($urlGenerator, $userRepository);

        $request = new Request();

        $reflection = new ReflectionClass(LoginFormAuthenticator::class);
        $method = $reflection->getMethod('getLoginUrl');

        $url = $method->invoke($authenticator, $request);

        $this->assertSame('/login', $url);
    }
}
