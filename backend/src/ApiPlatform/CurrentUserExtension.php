<?php

declare(strict_types=1);

namespace App\ApiPlatform;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Scopes every user-owned resource to the authenticated user at the data layer.
 *
 * The security firewall is the primary guard, but it is configuration and can be
 * misconfigured. This extension is the last line of defense: it runs on every
 * Doctrine collection and item query API Platform builds.
 *
 * See .context/coding-conventions/security.md -> Data isolation.
 */
class CurrentUserExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
	/**
	 * Every resource that belongs to a user MUST be listed here. Adding a
	 * user-owned entity without registering it exposes every row to every user.
	 *
	 * @var list<class-string>
	 */
	protected const OWNED_RESOURCES = [
		// e.g. Project::class,
	];

	/**
	 * Name of the property holding the owner on every owned resource.
	 */
	protected const OWNER_PROPERTY = 'user';

	public function __construct(
		private readonly Security $security,
	) {}

	public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, ?Operation $operation = null, array $context = []): void
	{
		$this->addOwnerFilter($queryBuilder, $resourceClass);
	}

	public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, ?Operation $operation = null, array $context = []): void
	{
		$this->addOwnerFilter($queryBuilder, $resourceClass);
	}

	private function addOwnerFilter(QueryBuilder $queryBuilder, string $resourceClass): void
	{
		if (!in_array($resourceClass, static::OWNED_RESOURCES, true)) {
			return;
		}

		$user = $this->security->getUser();

		// Never return silently: no user on an owned resource means the firewall
		// let an unauthenticated request through, and returning here would hand
		// back every row in the table.
		if (!$user) {
			throw new AccessDeniedException();
		}

		$rootAlias = $queryBuilder->getRootAliases()[0];

		$queryBuilder
			->andWhere(sprintf('%s.%s = :currentUser', $rootAlias, static::OWNER_PROPERTY))
			->setParameter('currentUser', $user)
		;
	}
}
