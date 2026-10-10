---
name: antigravity-code-style
description: >-
  Use this whenever the user asks for code, a code fix, or a code review, for any programming language.
  Enforces ANTIGRAVITY style: short, point-to-point, minimal code with zero over-engineering.
  Also use this whenever the user mentions antigravity code, antigravity skill, asks you to review or
  fix login/auth code, or complains that code is bloated, verbose, or over-engineered. Always consult
  this before writing or reviewing any code for this user, even for simple one-off snippets.
---

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
