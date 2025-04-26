<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class TokenAuthenticator extends AbstractAuthenticator
{
	/**
	 * @param string $apiToken
	 */
	public function __construct(private readonly string $apiToken)
	{
	}

	/**
	 * @param Request $request
	 * @return bool|null
	 */
	public function supports(Request $request): ?bool
	{
		return $request->headers->has('Authorization');
	}

	/**
	 * @param Request $request
	 * @return SelfValidatingPassport
	 */
	public function authenticate(Request $request): SelfValidatingPassport
	{
		$authHeader = $request->headers->get('Authorization');

		if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
			throw new CustomUserMessageAuthenticationException('Missing or malformed Authorization header.');
		}

		$token = substr($authHeader, 7);

		if ($token !== $this->apiToken) {
			throw new CustomUserMessageAuthenticationException('Invalid token.');
		}

		return new SelfValidatingPassport(
			new UserBadge('static_user', fn() => new StaticUser())
		);
	}

	/**
	 * @param Request $request
	 * @param TokenInterface $token
	 * @param string $firewallName
	 * @return null
	 */
	public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): null
	{
		return null;
	}

	/**
	 * @param Request $request
	 * @param AuthenticationException $exception
	 * @return JsonResponse
	 */
	public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
	{
		return new JsonResponse(['error' => 'Unauthorized'], 401);
	}
}
