<?php

declare(strict_types=1);

namespace App\Tests\Unit\ApiPlatform;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\ApiPlatform\CurrentUserExtension;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class CurrentUserExtensionTest extends TestCase
{
	private const UNOWNED_RESOURCE = 'App\Entity\SomethingPublic';

	private QueryNameGeneratorInterface $queryNameGenerator;

	protected function setUp(): void
	{
		$this->queryNameGenerator = $this->createStub(QueryNameGeneratorInterface::class);
	}

	public function testItLeavesAnUnownedResourceUntouched(): void
	{
		$queryBuilder = $this->buildQueryBuilder();
		$extension = new OwnedResourceCurrentUserExtension($this->buildSecurity(new User()));

		$extension->applyToCollection($queryBuilder, $this->queryNameGenerator, self::UNOWNED_RESOURCE);

		$this->assertNull($queryBuilder->getDQLPart('where'));
		$this->assertCount(0, $queryBuilder->getParameters());
	}

	public function testItScopesAnOwnedCollectionToTheCurrentUser(): void
	{
		$user = new User();
		$queryBuilder = $this->buildQueryBuilder();
		$extension = new OwnedResourceCurrentUserExtension($this->buildSecurity($user));

		$extension->applyToCollection($queryBuilder, $this->queryNameGenerator, stdClass::class);

		$this->assertNotNull($queryBuilder->getDQLPart('where'));
		$this->assertSame($user, $queryBuilder->getParameter('currentUser')?->getValue());
	}

	public function testItScopesAnOwnedItemToTheCurrentUser(): void
	{
		$user = new User();
		$queryBuilder = $this->buildQueryBuilder();
		$extension = new OwnedResourceCurrentUserExtension($this->buildSecurity($user));

		$extension->applyToItem($queryBuilder, $this->queryNameGenerator, stdClass::class, ['id' => 'any']);

		$this->assertNotNull($queryBuilder->getDQLPart('where'));
		$this->assertSame($user, $queryBuilder->getParameter('currentUser')?->getValue());
	}

	public function testItDeniesAnOwnedCollectionWhenThereIsNoAuthenticatedUser(): void
	{
		$extension = new OwnedResourceCurrentUserExtension($this->buildSecurity(null));

		$this->expectException(AccessDeniedException::class);

		$extension->applyToCollection($this->buildQueryBuilder(), $this->queryNameGenerator, stdClass::class);
	}

	public function testItDeniesAnOwnedItemWhenThereIsNoAuthenticatedUser(): void
	{
		$extension = new OwnedResourceCurrentUserExtension($this->buildSecurity(null));

		$this->expectException(AccessDeniedException::class);

		$extension->applyToItem($this->buildQueryBuilder(), $this->queryNameGenerator, stdClass::class, ['id' => 'any']);
	}

	public function testItShipsWithNoOwnedResourceRegistered(): void
	{
		$queryBuilder = $this->buildQueryBuilder();
		$extension = new CurrentUserExtension($this->buildSecurity(null));

		// The boilerplate registers no owned resource yet - nothing is filtered, and nothing throws.
		$extension->applyToCollection($queryBuilder, $this->queryNameGenerator, stdClass::class);

		$this->assertNull($queryBuilder->getDQLPart('where'));
	}

	private function buildQueryBuilder(): QueryBuilder
	{
		$queryBuilder = new QueryBuilder($this->createStub(EntityManagerInterface::class));

		return $queryBuilder
			->select('o')
			->from(stdClass::class, 'o')
		;
	}

	private function buildSecurity(?User $user): Security
	{
		$security = $this->createStub(Security::class);
		$security->method('getUser')->willReturn($user);

		return $security;
	}
}

/**
 * Registers an owned resource so the guarded paths are reachable. The shipped
 * extension has an empty list until a project adds its first user-owned entity.
 */
class OwnedResourceCurrentUserExtension extends CurrentUserExtension
{
	protected const OWNED_RESOURCES = [
		stdClass::class,
	];
}
