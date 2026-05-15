# PHP

### Control structures — always use braces

Never one-liner `if`/`else`/`for`/`while`. Always use braces, even for single statements.

```php
// ❌ wrong
if (!$user) return null;

// ✅ correct
if (!$user) {
    return null;
}
```

### Imports — never use fully qualified names inline

Always declare a `use` statement at the top of the file. Applies to everything: PHP native classes, Symfony, Doctrine, and any third-party class.

```php
// ❌ wrong
$now = new \DateTimeImmutable();

// ✅ correct
use DateTimeImmutable;
$now = new DateTimeImmutable();
```

### Arrays — always multiline

PHP arrays must always be written in multiline format — never inline on a single line, even with one element.

```php
// ❌ wrong
return new JsonResponse(['message' => 'Done.'], Response::HTTP_OK);

// ✅ correct
return new JsonResponse([
    'message' => 'Done.',
], Response::HTTP_OK);
```

This applies everywhere: `JsonResponse`, `return`, assignments, function arguments — any array literal.

**Nested arrays are not exempt** — every level must be multiline:

```php
// ❌ wrong
$stats = [
    'RETRAIT' => ['total' => 0, 'commission' => 0],
    'DEPOT'   => ['total' => 0],
];

// ✅ correct
$stats = [
    'RETRAIT' => [
        'total' => 0,
        'commission' => 0,
    ],
    'DEPOT' => [
        'total' => 0,
    ],
];
```

**Exceptions — keep inline:**
- PHP attributes: `#[Route('/path', methods: ['GET'])]`, `#[Groups(['book:list'])]`
- String manipulation helpers: `str_replace(['%', '_'], ['\%', '\_'], ...)`
- Inline membership checks: `in_array($x, ['a', 'b'], true)`

### Semicolons and commas on multiline expressions

For any multiline fluent chain or multiline array/function call, the terminating `;` or trailing `,` goes on its **own line**:

```php
// ✅ correct
$email = (new Email())
    ->from($from)
    ->to($to)
    ->subject('Hello')
;

// ❌ wrong
$email = (new Email())
    ->from($from)
    ->subject('Hello');
```

### Empty constructor body — no space between braces

```php
// ❌ wrong
public function __construct(
    private OrderRepository $orderRepository,
) { }

// ✅ correct
public function __construct(
    private OrderRepository $orderRepository,
) {}
```

---

## Quick Reference

| You're about to... | Instead |
|---|---|
| `new \DateTimeImmutable()` inline | `use DateTimeImmutable;` at top |
| `if (!x) return;` one-liner | Always braces: `if (!x) { return; }` |
