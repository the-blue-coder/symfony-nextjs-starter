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

### Controllers — never use `private` methods

**Controller classes must never declare `private` methods.** A controller action must stay a single public method that only calls into injectable classes (service, repository, normalizer, ...). If an action needs a helper step, that step is logic that belongs in a dedicated, injectable class — not a private method on the controller.

- ❌ No `private function` (or `protected function`, for the same reason) anywhere in a controller class.
- ✅ Extract the logic to whichever dedicated class fits it — a **service** for business/transformation logic, a **repository** for queries, a **normalizer**/DTO for response shaping — and inject it into the action.

```php
// ❌ wrong — private helper method in the controller
class OrderController extends AbstractController
{
    #[Route('/orders/{id}/summary', methods: ['GET'])]
    public function summary(Order $order): Response
    {
        return $this->json($this->buildSummary($order));
    }

    private function buildSummary(Order $order): array
    {
        // ... transformation logic ...
    }
}

// ✅ correct — helper logic moved to a dedicated injectable class
class OrderController extends AbstractController
{
    #[Route('/orders/{id}/summary', methods: ['GET'])]
    public function summary(Order $order, OrderService $orderService): Response
    {
        return $this->json($orderService->buildSummary($order));
    }
}
```

### Service naming

Every class in `src/Service/` MUST be named `*Service` and its file `*Service.php`. No exceptions - no `*Client`, `*Manager`, `*Handler`, `*Parser`.

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

### API Platform - routes MUST use kebab-case, never underscores

**Every API route/URI segment MUST use kebab-case (`-`). Underscores in a URI path are a hard error, no exceptions.** This applies to `uriTemplate`, custom operation paths, and any `#[Route]` used for an API endpoint.

- ✅ `/order-items`, `/payment-methods`, `/api/webhook-backup`
- ❌ `/order_items`, `/payment_methods`, `/api/webhook_backup`

This is independent from PHP/property naming (`snake_case` properties are fine per the Entity conventions above) - only the **URI itself** is affected. Property/field names in the request or response body follow the project's serialization convention, not this rule.

```php
// ❌ wrong - underscore in the URI
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/order_items'),
    ],
)]
class OrderItem {}

// ✅ correct - kebab-case URI
#[ApiResource(
    operations: [
        new GetCollection(uriTemplate: '/order-items'),
    ],
)]
class OrderItem {}
```

**Before adding or editing any `#[ApiResource]` operation or `#[Route]` on an API controller, check the URI for underscores.** A reviewer must reject the PR on sight if one is found - this is not a style nitpick, it's a project-wide contract with the frontend and any external consumer of the API.

### Idempotent endpoints - payment and order mutations

Any endpoint that triggers a **non-repeatable side effect** (charging a card, creating an order, sending a payment to a PSP) **MUST be idempotent**. A retried request (double-click, network timeout, client auto-retry) must produce the same result as the first request - never a second charge, a second order, a second email.

- The client sends an **idempotency key** (UUID generated once per user action, e.g. on button click) in a header (`Idempotency-Key`).
- The server checks the key **before** processing: if it has already been seen, return the stored result of the original request instead of re-executing the side effect.
- Store the key alongside the operation's result (dedicated table or column), scoped to the resource/user, with a reasonable expiry.
- **Never rely on the frontend disabling the button as the only protection** - it helps UX, but the guarantee must live server-side, since a slow network, a retry, or a direct API call bypasses it.

```php
// ❌ wrong - no idempotency check, every call charges the card
#[Route('/payment', methods: ['POST'])]
public function pay(Request $request, PaymentService $paymentService): Response
{
    $result = $paymentService->charge($request->toArray());
    return $this->json($result);
}

// ✅ correct - idempotency key checked before the side effect
#[Route('/payment', methods: ['POST'])]
public function pay(Request $request, PaymentService $paymentService): Response
{
    $idempotencyKey = $request->headers->get('Idempotency-Key');
    if (!$idempotencyKey) {
        throw new BadRequestHttpException('Missing Idempotency-Key header.');
    }

    $result = $paymentService->chargeIdempotent($idempotencyKey, $request->toArray());
    return $this->json($result);
}
```

### Twig Templates

- **Indentation**: use tabs, not spaces.

---

## Testing

- **PHPUnit** for unit and functional tests. Tests in `/backend/tests/`.
- Unit tests for **services** and **domain logic**.
- Functional tests for **API endpoints**.
- ✅ Test: services, complex domain logic. ❌ Skip: simple CRUD, config files.
- **TDD (test-first) is MANDATORY** for critical business logic and bug fixes — write the failing test before the implementation/fix, no exceptions.

---

## Quick Reference

| You're about to... | Instead |
|---|---|
| Query the DB from a service | Put the query in the repository |
| `$this->em->getRepository(Foo::class)` | Inject the repository via constructor |
| Name a class `*Client` or `*Manager` in `src/Service/` | Rename to `*Service` |
| Add a `private`/`protected` method to a controller | Move the logic to a service, repository, or normalizer |
| Add a new user-owned resource without updating `CurrentUserExtension` | Add it to `OWNED_RESOURCES` and throw `AccessDeniedException` if no user |

---

### Data isolation — CurrentUserExtension

**Every user-owned resource MUST be listed in `CurrentUserExtension::OWNED_RESOURCES`.** This is a security invariant, not a convenience.

The extension ships at `src/ApiPlatform/CurrentUserExtension.php`, already wired in and unit-tested. Adding a user-owned entity means adding one class-string to that array - do not re-implement the class. The code below explains *why* it throws; it is not a template to copy.

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
