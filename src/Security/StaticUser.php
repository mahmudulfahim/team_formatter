<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class StaticUser implements UserInterface
{

	/**
	 * @return string[]
	 */
	public function getRoles(): array
	{
		return ['ROLE_USER'];
	}

	/**
	 * @return void
	 */
	public function eraseCredentials(): void
	{
	}

	/**
	 * @return string
	 */
	public function getUserIdentifier(): string
	{
		return 'static_user';
	}
}