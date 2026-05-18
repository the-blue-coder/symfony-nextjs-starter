# Stack Versions

Last updated: March 2026

## Current Versions

| Package         | Version | Notes                           |
| --------------- | ------- | ------------------------------- |
| Next.js         | 16.1.6  | proxy.ts replaces middleware.ts |
| Symfony         | 7.x     |                                 |
| API Platform    | 3.x     |                                 |
| TanStack Query  | v5      |                                 |
| Tailwind CSS    | v4      | CSS-first config                |
| Zustand         | 5.x     |                                 |
| React Hook Form | 7.x     |                                 |
| Zod             | 3.x     |                                 |
| PHP             | 8.3     |                                 |
| PostgreSQL      | 16      |                                 |
| Redis           | 7       |                                 |
| Node            | 20 LTS  |                                 |

## Breaking Changes Log

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
