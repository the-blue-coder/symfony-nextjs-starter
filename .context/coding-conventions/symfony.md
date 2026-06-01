# Symfony

### Repository vs Service - data access layer

All SQL queries, DQL, QueryBuilder calls, and any Doctrine interaction belong in the **repository**, not the service.

- Repositories: sole responsibility is querying and returning entities.
- Services: contain business logic, call repositories, receive clean results.
- Never `createQueryBuilder`, `findBy`, raw SQL, or `getRepository` inside a service.
- **Always inject repositories via constructor** - never `$this->em->getRepository(Foo::class)`.
- `persist()` and `flush()` stay in the service - transaction orchestration, not data queries.

```php
// ❌ wrong
class OrderService
{
    public function __construct(private EntityManagerInterface $em) {}

    public function getPendingOrders(): array
    {
        return $this->em->getRepository(Order::class)->findBy(['status' => 'pending']);
    }
}

// ✅ correct
class OrderService
{
    public function __construct(private OrderRepository $orderRepository) {}

    public function getPendingOrders(): array
    {
        return $this->orderRepository->findPending();
    }
}
```

```php
// ❌ wrong - query in the service
class OrderService
{
    public function getPendingOrders(): array
    {
        return $this->em->createQueryBuilder()
            ->select('o')
            ->from(Order::class, 'o')
            ->where('o.status = :status')
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getResult()
        ;
    }
}

// ✅ correct - query in the repository
class OrderRepository extends ServiceEntityRepository
{
    public function findPending(): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.status = :status')
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getResult()
        ;
    }
}
```

### Controllers — thin, no business logic

Controllers must only: call service methods, pass data to templates, and handle HTTP concerns (redirects, 404s).

Never put data transformation, URL building, or any multi-step logic in a controller action — move it to a dedicated service.

```php
// ❌ wrong — business logic in the controller
public function list(ProductRepository $repo, ImageManager $im): Response
{
    $products = $repo->findAllActive();
    $data = [];
    foreach ($products as $product) {
        // ... image URL building, data assembly ...
        $data[] = $productData;
    }
    return $this->render('...', ['products' => $data]);
}

// ✅ correct — delegate to a service
public function list(ProductService $service): Response
{
    return $this->render('...', ['products' => $service->buildListData()]);
}
```

### Service naming

Every class in `src/Service/` MUST be named `*Service` and its file `*Service.php`. No exceptions - no `*Client`, `*Manager`, `*Handler`.

### Email sending - always via EmailService

All emails must be sent through `EmailService`, never directly via `MailerInterface`, `SesClient`, or any other transport. Add a dedicated method for each new email type, with its own Twig template in `templates/emails/`.

```php
// ❌ wrong
$this->mailer->send($email);

// ✅ correct
$this->emailService->sendBackupError($subject, $detail);
```

### Entity conventions

- **Money**: all money values stored as **integers (cents)**.
- **Timestamps**: `createdAt` / `updatedAt` on all entities - set via `#[ORM\PrePersist]` and `#[ORM\PreUpdate]`; the entity class **must** carry `#[ORM\HasLifecycleCallbacks]`.

### Migrations

After generating a migration with `doctrine:migrations:diff`, always remove the auto-generated comments before committing:
- The `/** Auto-generated Migration: Please modify to your needs! */` docblock on the class
- The `// this up() migration is auto-generated, please modify it to your needs` inline comments in `up()` and `down()`

### Twig Templates

- **Indentation**: use tabs, not spaces.

---

## Testing

- **PHPUnit** for unit and functional tests. Tests in `/backend/tests/`.
- Unit tests for **services** and **domain logic**.
- Functional tests for **API endpoints**.
- ✅ Test: services, complex domain logic. ❌ Skip: simple CRUD, config files.

---

## Quick Reference

| You're about to... | Instead |
|---|---|
| Query the DB from a service | Put the query in the repository |
| `$this->em->getRepository(Foo::class)` | Inject the repository via constructor |
| Name a class `*Client` or `*Manager` in `src/Service/` | Rename to `*Service` |
| Add a new user-owned resource without updating `CurrentUserExtension` | Add it to `OWNED_RESOURCES` and throw `AccessDeniedException` if no user |

---

### Data isolation — CurrentUserExtension

**Every user-owned resource MUST be listed in `CurrentUserExtension::OWNED_RESOURCES` (or equivalent).** This is a security invariant, not a convenience.

The extension scopes all collection and item queries to the current user. When the resource is in the protected list and no authenticated user is found, **throw `AccessDeniedException` — never `return` silently.** A silent return means an unauthenticated request hitting a future public route returns every row for every user with no error.

```php
// ❌ wrong — silent pass-through exposes all rows on unauthenticated access
private function addFilter(QueryBuilder $qb, string $resourceClass): void
{
    if (!in_array($resourceClass, self::OWNED_RESOURCES, true)) {
        return;
    }

    $user = $this->security->getUser();
    if (!$user) {
        return; // ← no user = no filter = full table exposed
    }

    $qb->andWhere('o.user = :user')->setParameter('user', $user);
}

// ✅ correct — owned resource with no user → hard fail
private function addFilter(QueryBuilder $qb, string $resourceClass): void
{
    if (!in_array($resourceClass, self::OWNED_RESOURCES, true)) {
        return;
    }

    $user = $this->security->getUser();
    if (!$user) {
        throw new AccessDeniedException(); // ← defense-in-depth: firewall can be misconfigured
    }

    $qb->andWhere('o.user = :user')->setParameter('user', $user);
}
```

The `access_control` firewall is the primary guard, but it is configuration — it can be misconfigured or bypassed. The extension is the last line of defense at the data layer.
