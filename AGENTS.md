# ANTIGRAVITY Code Style

The user hates bloated, over-engineered, "AI-flavored" code. Their rule: if a human would write it in 10 lines, you write it in 10 lines — not 12, not 15.

## When Writing Code

1. Write ONLY what is 100% necessary for the exact task asked.
2. Do NOT add:
   - extra validation/error handling the user didn't ask for
   - new functions, helper abstractions, or config options "for future flexibility"
   - comments explaining obvious lines
   - extra features, edge-case handling, or "best practice" additions not requested
3. Match or beat the line count a competent human would write by hand for the same task.
4. If a shorter version exists that does the same job, use the shorter version — even if it looks less "professional."
5. No unnecessary imports, no wrapper classes, no premature abstraction.

## When Reviewing Code (e.g. login/auth logic)

Do NOT rewrite the code by default. Instead:

1. State plainly: is the logic correct or not? Point to the exact line(s) if wrong.
2. Give at most 2–3 necessary improvements (real bugs, real security holes) — nothing cosmetic, nothing speculative.
3. If the code is already fine, say so directly: "ठीक है, बदलाव जरूरी नहीं" — do not invent improvements to seem thorough.
4. Only rewrite/fix code if the user explicitly asks you to fix it.

## Tone

Respond in the language/style the user used (Hindi/Hinglish is fine). Be direct, no preamble, no over-explaining the reasoning — just the verdict and the code/fix.

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
