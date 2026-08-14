# Skill quality evaluation in an AI harness

Use this only when the user wants a qualitative review of a Skill against Agent Skills best practices. `agents lint` is still the first pass; this harness evaluation covers judgement-heavy areas that static lint cannot reliably decide.

## When to run this

Run this after `agents lint --source=all` passes, or when the user asks whether a Skill is too broad, too generic, too large, poorly triggered, or missing progressive disclosure.

## Evaluation workflow

1. Identify the Skill path, usually `.agents/skills/<slug>/SKILL.md` or `packages/<pkg>/SKILL.md`.
2. Read the Skill frontmatter and body. If the Skill references companion files, read only the files whose trigger conditions apply to the evaluation.
3. Compare the Skill against the Agent Skills guidance:
   - Description uses imperative trigger phrasing such as "Use when..." and focuses on user intent rather than implementation.
   - Scope is a coherent unit of work: neither so narrow that many Skills must load together nor so broad that it activates imprecisely.
   - Body spends context on project-specific procedures, gotchas, defaults, validation loops, and fragile sequencing.
   - Large or reference-heavy content uses progressive disclosure with explicit instructions for when to load companion files.
   - Multi-step workflows include concrete validation steps rather than only declarative advice.
4. Check AI Pillar frontmatter. `ai-pillars` is optional, but if the Skill performs a ticket toolchain stage it should declare the matching pillar: `ticketed` for creating tickets or applying `ai-ticketed`, `triaged` for triaging tickets or applying `ai-triaged`, `implemented` for implementation work or applying `ai-implemented`, and `reviewed` for review work or applying `ai-reviewed`. Recommend renaming legacy `ai-refined` label usage to `ai-triaged`. Also flag declared pillars that the body does not actually support.
5. Create 8-10 should-trigger prompts and 8-10 near-miss should-not-trigger prompts. Keep them realistic: include casual phrasing, file paths, partial context, typos, and larger tasks where the Skill need is embedded.
6. If the harness exposes Skill activation logs, run each prompt enough times to estimate whether the Skill triggers when it should. If not, do a written trigger-risk review instead and label it as an inference.
7. Report concise findings with severity, evidence, and an implementable recommendation. Separate static lint failures from qualitative recommendations.

## Harness prompt

Paste this into the AI harness that has the Skill installed, replacing the placeholders:

```text
Evaluate `<SKILL_PATH>` against Agent Skills best practices.

First run or confirm the static gate:

`npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents lint --source=all`

Then do a qualitative review of the Skill:

1. Inspect frontmatter. Confirm `name` matches the slug and `description` is concise, imperative, user-intent focused, and clear about when to use the Skill.
2. Inspect the body. Check for coherent scope, project-specific guidance, useful defaults, gotchas, procedures over declarations, validation loops, and progressive disclosure for large reference material.
3. Check AI Pillar frontmatter. `ai-pillars` is optional, but if the body performs a ticket toolchain stage then the matching pillar should be present: `ticketed` for creating tickets or applying `ai-ticketed`, `triaged` for triaging tickets or applying `ai-triaged`, `implemented` for implementation work or applying `ai-implemented`, and `reviewed` for review work or applying `ai-reviewed`. Recommend renaming legacy `ai-refined` label usage to `ai-triaged`. Flag any declared pillar that the body does not actually support.
4. Draft realistic trigger evals:
   - 8-10 prompts that should trigger the Skill.
   - 8-10 near-miss prompts that should not trigger it.
5. If this harness exposes Skill activation logs, run the evals and record trigger results. If it does not, do not pretend to have runtime evidence; provide a trigger-risk assessment from the description text.
6. Return findings first, ordered by severity. For each finding include:
   - Evidence from the Skill.
   - Why it affects trigger accuracy or execution quality.
   - A concrete edit the maintainer can apply.

Keep the response short enough for a maintainer to act on it immediately.
```

## Output shape

Use this format:

```markdown
## Findings

- **[severity] Title** — Evidence from the Skill and why it matters.
  Recommendation: Concrete edit.

## Trigger Eval Sketch

Should trigger:
- ...

Should not trigger:
- ...

## Static Gate

- `agents lint --source=all`: pass/fail/not run
```
