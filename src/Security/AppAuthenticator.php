<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AppAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function authenticate(Request $request): Passport
    {
        $username = $request->request->get('_username');
        if (null === $username || '' === $username) {
            throw new AuthenticationException('Username not provided.');
        }

        $password = $request->request->get('_password');
        if (null === $password || '' === $password) {
            throw new AuthenticationException('Password not provided.');
        }

        $csrfToken = $request->request->get('_csrf_token');
        if (null === $csrfToken || '' === $csrfToken) {
            throw new AuthenticationException('CSRF token not provided.');
        }

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $username);

        return new Passport(
            new UserBadge($username),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        $roles = $token->getUser()->getRoles();
        $redirectRoute = $this->getRedirectRouteBasedOnRole($roles);

        if ($redirectRoute) {
            return new RedirectResponse($this->urlGenerator->generate($redirectRoute));
        } else {
            throw new \Exception('No route found for user role.');
        }
    }
    private function getRedirectRouteBasedOnRole(array $roles): ?string // Gestion des rôles utilisateur pour la redirection après connexion
    {
        if (in_array('ROLE_ADMIN', $roles, true)) {
            return 'admin';
        } elseif (in_array('ROLE_USER', $roles, true)) {
            return 'app_profile';
        }

        return null;
    }
    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
