# Stack Versions

Last updated: May 2026

## Current Versions

| Package         | Version | Notes                           |
| --------------- | ------- | ------------------------------- |
| Next.js         | 16.1.6  | proxy.ts replaces middleware.ts |
| Symfony         | 8.1.x   | requires PHP 8.4+               |
| API Platform    | 4.x     |                                 |
| TanStack Query  | v5      |                                 |
| Tailwind CSS    | v4      | CSS-first config                |
| Zustand         | 5.x     |                                 |
| React Hook Form | 7.x     |                                 |
| Zod             | 3.x     |                                 |
| PHP             | 8.4     |                                 |
| PostgreSQL      | 16      |                                 |
| Redis           | 7       |                                 |
| Node            | 20 LTS  |                                 |

## Breaking Changes Log

### 2026-05 - Symfony 8.1 upgrade

- Symfony 7.4 → 8.1: requires PHP 8.4+ (Dockerfile bumped 8.3 → 8.4)
- All `symfony/*` constraints moved to `8.1.*`
- `doctrine/doctrine-bundle` 2.18 → 3.2 (2.x caps `symfony/cache` at `^7.0`, blocks SF8)
- ORM 3 uses native PHP 8.4 lazy objects: removed no-op `doctrine.yaml` options
  `use_savepoints`, `auto_generate_proxy_classes`, `proxy_dir`,
  `enable_lazy_ghost_objects`, `report_fields_where_declared` (all hard-removed in
  doctrine-bundle 3.0 — they now raise "Unrecognized option")
- `firebase/php-jwt` 6 → 7 (clears CVE-2025-45769, weak encryption)
- Removed dead `LexikJWTAuthenticationBundle` + `GesdinetJWTRefreshTokenBundle` from
  `config/bundles.php`: skeleton leftovers, unused since auth is Clerk-based
  (`ClerkAuthenticator`). lexik had no SF8-compatible version pulled and gesdinet
  was registered but never installed.

### 2026-03 - Initial release

- Next.js 16: proxy.ts replaces middleware.ts
- Tailwind v4: CSS-first config, no tailwind.config.ts

## Update Process

When Dependabot opens a PR:

1. Check the package changelog for breaking changes
2. Test locally
3. If conventions change → update CLAUDE.md
4. Update this file (version + breaking changes log)
5. Merge the PR

```

---

### 3. Active Dependabot sur GitHub
```

GitHub repo → Settings → Security →
Code security and analysis → Dependabot alerts → Enable
→ Dependabot security updates → Enable
→ Dependabot version updates → Enable (auto via .yml)
