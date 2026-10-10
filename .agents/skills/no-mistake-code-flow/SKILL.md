---
name: no-mistake-code-flow
description: >-
  A code-delivery workflow that catches and fixes mistakes BEFORE showing code to the user. Use this whenever writing, editing, fixing, or reviewing code for Aman's projects (CRM/expert-panel, SocialFlow/ANTIGRAVITY, Akaria tools, or any one-off script). Make sure to trigger this any time code is about to be delivered, edited, or debugged — even for small snippets — since the whole point is to never hand over broken or half-checked code. Pairs with antigravity-code-style (style/minimalism) — this skill is about correctness, not style.
---

# No-Mistake Code Flow

Goal: never deliver code with a bug, typo, missing import, broken logic, or unhandled edge case. If a mistake is found at any point, fix it silently and re-check — don't just point it out and stop.

## The flow (run every time, before showing code)

1. **Write the code** normally (respecting antigravity-code-style: short, minimal, no over-engineering).
2. **Self-check pass** — read the code back line by line and check for:
   - Syntax errors, typos in variable/function/table/column names
   - Undefined variables, missing imports/requires
   - SQL: correct table/column names, correct joins, quoting, injection safety (use prepared statements)
   - Off-by-one errors, wrong operators (`=` vs `==`, `&&` vs `||`)
   - Null/empty/undefined handling — what happens on missing input, empty result set, failed API call
   - Auth/permission checks present where the CRM has role-based access (admin vs employee vs expert)
   - Matches what was actually asked — no scope drift, no invented requirements
3. **Run it if possible.** For scripts/backend code: actually execute it (bash_tool) or at minimum lint/syntax-check it. For PHP without a runnable environment here, do a manual dry-run trace of the logic instead of skipping verification.
4. **If a mistake is found:** fix it immediately, then repeat step 2 on the fixed version. Do not surface the broken version to the user "for reference" — only the corrected one goes out.
5. **Only after a clean pass**, deliver the code, briefly noting anything non-obvious you changed or fixed (one line, not a essay).

## Rules

- Never say "this might have a bug, please check" as a substitute for actually checking it yourself first.
- If something is genuinely ambiguous (e.g., a business rule not yet specified — like the CRM's lead auto-assignment logic), ask one direct question instead of guessing and shipping wrong logic.
- For CRM/PHP work specifically: always check department/role-based access logic (admin vs employee vs expert panel) isn't accidentally bypassed by the change.
- Keep fixes minimal — fix the actual bug, don't refactor unrelated code in the same pass (that's antigravity-code-style's job, not this one's).
