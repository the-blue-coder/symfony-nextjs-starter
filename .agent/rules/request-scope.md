---
description: "Reminds the agent to suggest /spec for substantial requests"
---

# Request Scope Rule

When the user sends a request that looks substantial - implementing a new feature, new page, new component, new service, new endpoint, refactoring, migration, architecture change, or anything described as "from scratch" - and it is NOT a question (no question mark) and NOT a small fix (typo, rename, color, label), then:

Before writing any code, ask the user:

> "This looks like a non-trivial change - do you want to go through /spec first to spec it out, or should I just go ahead?"

Wait for the answer before proceeding.

This rule does NOT apply to:
- Questions ending with `?`
- Explicit small fixes: fix, bug, typo, rename, change text/color/style/label
- Tasks that clearly touch ≤ 3 files with no new feature
