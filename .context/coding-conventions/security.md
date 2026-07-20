# Security

> Cross-cutting - read this whenever you touch auth, an API route, a webhook, an env var, or nginx.
>
> Security and data isolation are listed under **Never simplify away** in `global.md`'s Absolute Directive. The decision ladder never applies to the rules below: "the minimum that works" is not a valid argument against any of them.

---

## Trust boundaries

Four places where untrusted input enters this stack. Validate at the boundary, not deeper.

| Boundary | Entry point | Guard |
| --- | --- | --- |
| Browser → Next.js | `frontend/src/proxy.ts` | Clerk `auth.protect()` on the `(dashboard)` route group |
| Browser → API | firewall `api` in `backend/config/packages/security.yaml` | `App\Security\ClerkAuthenticator` (JWT verified against Clerk's JWKS) |
| Clerk → API | `POST /api/webhook/clerk` | svix HMAC signature check inside the controller |
| Backup runner → API | `POST /api/webhook/backup` | `X-Backup-Secret` header check inside the controller |

**The `^/api/webhook` firewall is `security: false`.** For those two routes, the signature check inside the controller is the *only* thing between the internet and your database. Never add a route under `/api/webhook` without one.

---

## Clerk JWT - what is verified, and what is not

`ClerkAuthenticator::authenticate()` verifies the **signature** against Clerk's JWKS and the **expiry** (`JWT::decode` enforces `exp` / `nbf`). Nothing else is checked.

- **Never make an authorization decision from a token claim.** Only `sub` (the Clerk user id) is trusted, and only to look up the local `User`.
- **The email claim is not verified.** `authenticate()` falls back to `$decoded->email` when auto-provisioning a user. `ClerkService::extractPrimaryEmail()` (webhook path) is the source of truth for email - never branch on a token's email.
- **The user identifier is `clerkUserId`, never the email** - see `User::getUserIdentifier()` and the provider's `property: clerkUserId`. Emails change and can be re-registered. Never key a user, a permission, or an ownership check on one.
- **A valid token auto-provisions a `User` row** on first sight, independently of the webhook. Any code assuming "a `User` row exists ⇒ the webhook ran" is wrong.
- If the Clerk instance ever serves more than one application, verify the `azp` claim against the frontend origin before trusting the token. Not currently done.

---

## Data isolation - the last line of defense

Every user-owned resource MUST be scoped to the current user at the **data layer**, not only by the firewall. The firewall is configuration and can be misconfigured; the query extension cannot be bypassed by a routing mistake.

The full rule, with the mandatory `AccessDeniedException` on a missing user, lives in `coding-conventions/symfony.md` → *Data isolation - CurrentUserExtension*. Read it before adding any entity that belongs to a user.

> The extension ships at `backend/src/ApiPlatform/CurrentUserExtension.php` with an **empty** `OWNED_RESOURCES` list, already wired into API Platform through `autoconfigure`. Adding a user-owned entity means adding one class-string to that array - never re-author the class, and never add the entity without the array entry.
>
> It also assumes the owner property is named `user` (`OWNER_PROPERTY`). Override that constant if a resource names it differently.

- Never expose a raw Doctrine entity whose serialization group leaks another user's data - check `#[Groups]` on every new field.
- Never accept a user id, an owner id, or a tenant id from the request body. Derive it from the authenticated token, always.

---

## Webhooks

- **Compare secrets and signatures with `hash_equals()`, never `===`.** Both shipped webhooks do this (`ClerkController::verifySignature()`, `BackupController::__invoke()`) - a plain comparison leaks the secret through timing.
- **Verify the signature before parsing the body**, and before any side effect. `ClerkController` returns `401` before touching `json_decode`.
- **Reject stale timestamps.** A signature alone does not stop a replay - a captured request stays valid forever without a freshness check. `ClerkSignatureService` enforces a ±5 minute window (`TOLERANCE_SECONDS`); any new webhook must do the same.
- **Signature verification belongs in a service, not the controller.** `ClerkSignatureService` is unit-tested against replay, tampering, and header-stripping. A private method on a controller cannot be tested and will not be.
- Sign over the **raw body** (`$request->getContent()`), never over a re-encoded array - re-encoding changes bytes and breaks the signature.
- A webhook that triggers a non-repeatable side effect must also be idempotent - see `symfony.md` → *Idempotent endpoints*.

---

## Secrets and env vars

`.env` files are **committed**. That single fact drives every rule here.

- **A committed `.env` never holds a real secret value.** Placeholders only (`change-me-in-production`, `whsec_change-me`, `CHANGE_ME`, or empty). Real values go in `.env.local` (gitignored) locally, and in the server's environment in production.
- **Anything prefixed `NEXT_PUBLIC_` is baked into the client bundle at build time and is public.** Never put a secret behind that prefix. `CLERK_SECRET_KEY` and `MERCURE_JWT_SECRET` correctly have no prefix - keep it that way.
- **`APP_SECRET` and `POSTGRES_PASSWORD` ship as dev-grade placeholders** (`change-me-in-production`, `app`). Both must be replaced with generated values before a project goes to production - this is a deploy checklist item, not an optional hardening step.
- Never log, never echo, and never put in an error message: tokens, secrets, `DATABASE_URL`, or anything derived from them.
- Never write a real secret into `.context/`, `CHANGELOG.md`, a spec, or a commit message.

---

## CORS

`nelmio_cors.yaml` runs with `origin_regex: true` and applies to `^/`, so `CORS_ALLOW_ORIGIN` is a **regular expression**, not a literal origin.

- **Anchor it with `^` and `$`, and escape every dot.** The shipped default is the reference to copy:
  ```
  CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$
  ```
- Never `.*`, never `*`, never an unanchored fragment - `^https://app\.example\.com$` matches one origin; `https://app.example.com` matches `https://app-example-com.attacker.test` too.
- Never widen it to a whole domain (`^https://.*\.example\.com$`) unless every subdomain is under your control - one forgotten DNS record is a full CORS bypass.
- `allow_headers` is `Content-Type` + `Authorization` only. Add a header there only when a real request needs it.

---

## Error responses - never leak internals

The contract is `{ "message": "..." }` (see `global.md` → *Error Handling*). The message is user-facing.

- **Never return an exception message straight to the client.** A `pg_dump` or S3 failure message carries the database host, the database user, or a bucket path. `BackupController` returns a generic `Backup failed.` and keeps the detail server-side - do the same in every new handler.
- Never return a stack trace, a file path, a SQL fragment, or a class name.
- Authentication failures stay generic - `ClerkAuthenticator::onAuthenticationFailure()` returns the reason (`Token has expired.`, `Invalid token signature.`), which is acceptable for a token check but must never grow into "no user with that email".
- Do not let the response distinguish "does not exist" from "not yours" for a user-owned resource - both are `404`.

---

## Rate limiting

**There is none in this project today** - no `framework.rate_limiter` config, no nginx `limit_req`. Anything that can be brute-forced or that costs money needs one before it ships:

- Webhook endpoints (unauthenticated by design).
- Any endpoint that sends an email, triggers a backup, calls a paid third-party API, or writes a file.
- Any endpoint accepting a user-supplied identifier that could be enumerated.

Use Symfony's `framework.rate_limiter` and key the limiter on the authenticated user where there is one, on `$request->getClientIp()` otherwise. That IP is only trustworthy because nginx sets `X-Forwarded-For` - see `infra.md` → *Nginx + Certbot* for the renewal hook that keeps it that way.

---

## Transport and HTTP headers

Both nginx configs redirect `80 → 443`, terminate TLS via certbot, and set these at **server** level:

```nginx
add_header Strict-Transport-Security "max-age=2592000" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
server_tokens off;
```

- **Server level, not inside `location /`.** Certbot rewrites that block on every renewal and would drop them - the same trap that strips the proxy headers (see `infra.md` → *Nginx + Certbot*).
- **`add_header` does not merge.** Adding one `add_header` inside a `location` block silently discards every header inherited from the server block. If you add one there, re-declare all of them.
- **HSTS ships at 30 days deliberately.** Raise it to `max-age=31536000` once the domain is confirmed HTTPS-only for good, and add `includeSubDomains` only when every subdomain is HTTPS too - a wrong HSTS value cannot be taken back for the length of its max-age.
- `X-Frame-Options: SAMEORIGIN` is on the **frontend** config only. CSP belongs there too - the API host has no UI to frame.

- `client_max_body_size 20M` is set on the backend only. Any upload endpoint must enforce its own size, extension, and MIME limits in PHP as well - nginx's limit is a guardrail, not validation.

---

## Frontend

- **The route guard lives in `src/proxy.ts` and nowhere else.** Never re-implement an auth check in a page, a layout, or a hook - see `ai-workflow-rules.md` → *Protected Files*.
- A guard in `proxy.ts` protects **navigation**, not data. The API re-authenticates every request independently; never rely on the frontend having hidden a button.
- Never put a secret in a client component or in any `NEXT_PUBLIC_*` value.
- `dangerouslySetInnerHTML` is allowed for exactly one thing in this project: the blocking theme script in `layout.tsx` (see `nextjs.md`). Never use it with a value that comes from the API, the URL, or user input.
- Never build a redirect target from unvalidated user input - `proxy.ts` validates the locale against `routing.locales` before redirecting. Do the same for any new redirect.

---

## Dependencies

See `global.md` → *Library Usage* for when a new dependency is justified. Security-specific:

- Never hand-roll crypto, token parsing, signature verification, or password handling. Use the library.
- Vet maintenance activity and known CVEs before proposing a dependency, and never add one without the user's explicit approval.

---

## Quick Reference

| You're about to... | Instead |
|---|---|
| Compare a secret with `===` or `==` | `hash_equals()` |
| Add a route under `/api/webhook` | Verify a signature in the controller - the firewall is `security: false` |
| Trust `email` (or any claim) from the JWT | Only `sub` is trusted; email comes from the Clerk webhook |
| Key a user or an ownership check on email | `clerkUserId` |
| Take an owner id / user id from the request body | Derive it from the authenticated token |
| Add a user-owned entity | Register it in `CurrentUserExtension` (create it if absent) |
| Return `$e->getMessage()` to the client | Generic `{ "message": ... }`, log the detail server-side |
| `404` vs `403` on someone else's record | Always `404` - do not confirm it exists |
| Put a real secret in a committed `.env` | Placeholder in `.env`, real value in `.env.local` / server env |
| Put a secret behind `NEXT_PUBLIC_*` | Drop the prefix - `NEXT_PUBLIC_*` ships to the browser |
| Set `CORS_ALLOW_ORIGIN` to `.*` or an unanchored value | Anchored regex with escaped dots: `^https://app\.example\.com$` |
| Ship an email / payment / backup endpoint with no limiter | Add `framework.rate_limiter` first |
| Re-check auth in a page or hook | It belongs in `src/proxy.ts` |
