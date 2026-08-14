---
name: using-agents-cli
description: Use when discovering, installing, upgrading, removing, syncing, checking, linting, publishing, or preparing local draft agent assets for publishing with the agents CLI.
---

# Lendable agent assets — use the `agents` CLI

**Trigger:** the user wants to install, upgrade, remove, sync, check, lint, qualitatively evaluate, or **publish** a Lendable shared agent asset (any `@lendable/ai-agent-*` package — skills, subagents, slash commands, hooks, MCP server configs), or any `@lendable/ai-*` runbook package.

## The rule

Always use `@lendable/ai-agents-cli` ("the agents CLI"). Don't edit `package.json` deps directly, don't run `pnpm add` / `npm install` / `yarn add` on a `@lendable/ai-*` agent-asset package, and don't hand-craft files under `.agents/`. The CLI does work the user can't see (per-harness symlinks, sidecar+merge into shared config files, manifest+integrity tracking) that gets silently skipped by manual edits.

Always use the CLI through one canonical invocation form: `npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents <cmd>`. It works identically in JS and non-JS repos, on first-time and subsequent calls, regardless of which package manager (or none) the consumer uses. Don't switch to `npx @lendable/ai-agents-cli@latest <cmd>` — npm 11's `npx` mis-parses `@scope/pkg@tag` (treats it as a script name in JS repos, ENOENTs without a `package.json` in non-JS repos). Don't switch to `pnpm exec agents` / `yarn agents` either — picking between them is exactly the kind of guess that wastes tokens and goes wrong.

```bash
# Discover @lendable/ai-* skills, subagents, slash commands, hooks, and
# MCP server configs available in the registry before installing anything.
# Covers every @lendable/ai-* tier (`ai-agent-*`, `ai-cli-*`, bare `ai-*`).
# A query matches name/description/keywords (e.g. `agents search okta`
# finds the Okta skill via its description). `--json` emits a stable
# shape for an agent to parse before calling add.
npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents search
npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents search review
npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents search --json

# First time on a machine? Get a setup prompt for your AI coding agent that
# walks through Lendable's JFrog Set Me Up + `npm login` flow, adds the
# conventions block to the repo's `.npmrc`, and deletes any stale
# @lendable:registry= line in the repo's `.npmrc` (the convention uses
# only the default registry=). Skip if already done.
npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents bootstrap --copy

# Install (works in any repo — JS, Python, Go, Rust, anything):
npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents add @lendable/ai-<name>

# Upgrade everything the CLI manages:
npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents upgrade

# Upgrade just one package:
npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents upgrade @lendable/ai-<name>

# Uninstall (cleans up .agents/ + per-harness mirrors):
npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents remove @lendable/ai-<name>

# Re-materialise without changing what's installed (idempotent):
npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents sync

# CI gate — exits non-zero on drift between installed packages and .agents/:
npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents check

# Quality gate — validates Skill frontmatter in workspace packages and .agents/:
npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents lint --source=all

# CI quality gate — fail on warnings and emit GitHub annotations:
npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents lint --strict --reporter=github

# Publish a hand-authored or locally-edited asset to `@lendable/ai-agent-<slug>`:
npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents publish <slug>

# Show what's installed:
npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents list
```

Why `@latest` and not the lockfile-pinned local install? The CLI is a *materialiser* — its output is determined by the consumer's `package.json` deps and the integrity-pinned tarballs recorded in `.agents/lendable-manifest.json`, not by which CLI version performs the materialisation. A behaviour-changing CLI release would be a major-version bump per semver and `@latest` would pick it up correctly. The single registry roundtrip (npm's `latest`-tag resolution; tarball is locally cached) is negligible against the simplicity of one form for every repo.

Why `npm exec --yes --package=...` and not `npx`? `npx` is what most people instinctively reach for, but npm 11 broke `npx @scope/pkg@tag <cmd>` invocations: in a project directory it parses `@scope/pkg@tag` as a script name and fails with "Missing script"; in a non-JS directory it errors with `ENOENT: package.json`. `npm exec --yes --package=<spec> -- <bin> <args>` is the underlying form npx wraps and works correctly in both contexts on every npm version that ships with Node 18+.

## Why the ceremony

A single `agents add` call does several things you'd otherwise miss:

- **Installs the package** — picks up the consumer's existing pm (pnpm/npm/yarn) if the repo has a `package.json`, OR fetches the tarball directly from the npm registry if the repo has no JS infrastructure (Python, Go, Rust, etc. — no `package.json` is created).
- **Materialises canonically.** Writes the kind-specific `.agents/` path based on the package's `lendable.kind`: directories for `skill` / `hook`, flat files for `agent` / `command` / `mcp`.
- **Mirrors per harness.** Creates symlinks under `.claude/`, `.codex/`, etc. for harnesses that don't read `.agents/<kind>/` natively, AND deep-merges into shared config files (`.mcp.json`, `.claude/settings.json`, `.codex/hooks.json`) without clobbering user-added entries.
- **Tracks integrity.** `.agents/lendable-manifest.json` records `(version, integrity, paths)` so `agents check` catches drift in CI.
- **Checks quality.** `agents lint` validates Skill frontmatter so every `SKILL.md` has a stable `name`, a useful `description`, and no malformed YAML before it spreads across repos.

## Check vs lint

`agents check` answers "is `.agents/` in sync with installed packages and approved hand-authored assets?" It is the drift/sync gate and should stay green after `agents sync`.

`agents lint` answers "are the Skills themselves well-formed?" It checks workspace skill packages and materialised `.agents/skills/*/SKILL.md` files. Use `--source=workspace` while authoring local packages, `--source=materialized` to inspect only materialised repo-local skills, and `--source=all` when you want the combined local view. Warnings are advisory by default so existing repos can adopt gradually; add `--strict` in CI when you want warnings to fail the job.

If the user asks whether a Skill follows Agent Skills best practices beyond static frontmatter lint, read `references/skill-quality-evaluation.md`. Load it only for that qualitative review path; it contains the trigger-eval workflow and harness prompt for judging description quality, scope, body structure, progressive disclosure, AI Pillar frontmatter alignment, and validation guidance without bloating this always-loaded runbook.

For GitHub Actions, do not try to make lint an input to `@lendable/ai-agents-check-action`. Keep the check action focused on sync/drift, then add a separate raw CLI step after it:

```yaml
- name: Lint agent Skills
  run: npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents lint --strict --reporter=github
```

## Discovering available skills

If the user asks "what skills are available?", "is there a skill for X?", or otherwise wants to know what they can install before naming a specific package, use `agents search` rather than guessing at names or pointing them at the monorepo. Results are always scoped to the `@lendable/ai-` prefix and cover the full asset surface the CLI manages — skills, subagents, slash commands, hooks, and MCP server configs — across every `@lendable/ai-*` tier (`ai-agent-*`, `ai-cli-*`, bare `ai-*`).

```bash
# List every @lendable/ai-* package available in the configured registry:
npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents search

# A query matches name/description/keywords — `okta` finds the Okta-protect skill via its description:
npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents search okta

# Stable JSON shape suitable for an AI agent to parse before deciding what to add:
npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents search --json
```

The `--json` output is `[{ name, version, description, keywords, date, links }, ...]` — enough for an agent to pick a package based on the user's stated need (matching against `description` and `keywords`) and then hand the chosen `name` to `agents add`. `--size=<n>` (1..250, default 50) and `--from=<n>` are available for pagination but are rarely needed; the namespace is small.

The namespace-wide scope also surfaces non-skill packages (`@lendable/ai-agents-cli` itself, `@lendable/ai-docs`, `@lendable/ai-agents-check-action`, `@lendable/ai-agents-upgrade-action`) — match on `description` / `keywords`, not just `name`, when an agent is picking what to install.

Discovery is read-only. It never installs anything — after picking a candidate, run `agents add <name>` to actually materialise it.

## First-time setup

If any `agents` invocation fails with an Artifactory auth error, an `ERR_SSL_TLSV1_ALERT_INTERNAL_ERROR` against `registry.npmjs.org`, or anything that looks registry/auth-related, the consumer's machine or repo isn't set up yet. Send them to the getting-started docs:

**<https://lendable.github.io/ai/packages/ai-agents-cli/getting-started/>**

That page covers everything end-to-end — Node install, JFrog Set Me Up + `npm login`, the repo's `.npmrc` conventions block, the `pnpm-workspace.yaml` split for pnpm repos, and the troubleshooting matrix for stale registry overrides, Safe Chain, etc. It also reproduces the full `agents bootstrap` prompt verbatim so a coding agent can paste it and walk the user through the flow even when `npm exec` itself can't reach the registry yet (the chicken-and-egg case).

## Publishing

`agents publish <slug>` publishes a hand-authored or locally-edited asset from its canonical `.agents/` location in the consumer's repo to `@lendable/ai-agent-<slug>` on Artifactory. The monorepo `Lendable/ai` stays the source of truth — every published change goes through a real PR there — but the round-trip is one command. Use this **instead of** cloning `Lendable/ai` to hand-craft `packages/ai-agent-<slug>/`.

```bash
# First publish (creates @lendable/ai-agent-my-skill via a new PR):
npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents publish my-skill

# Iterate — re-running on the same slug appends to the existing PR
# and re-pins the consumer repo's lockfile to the new snapshot:
npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents publish my-skill

# Bump higher than patch (default):
npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents publish my-skill --bump minor

# Dry run — print the FileChanges payload + planned PR title/body, no mutations:
npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents publish my-skill --dry-run
```

What the command does on a fresh publish:

1. Reads the kind-specific draft location: `.agents/skills/<slug>/` and `.agents/hooks/<slug>/` are directories; `.agents/agents/<slug>.md`, `.agents/commands/<slug>.md`, and `.agents/mcp/<slug>.json` are flat files. MCP drafts are published as package-root `mcp.json`.
2. Checks the existing publish PR branch first when re-running, otherwise `Lendable/ai/packages/ai-agent-my-skill/` on `main`. If absent, the PR creates it; if present, the PR updates it (additions + deletions diffed against the relevant upstream tree).
3. Opens a PR `agents-publish/<your-gh-user>/my-skill` against `Lendable/ai` with the `preview` label, using the GitHub GraphQL API (no local clone of `Lendable/ai`).
4. Waits for the `preview.yml` snapshot CI workflow to publish `@lendable/ai-agent-my-skill@pr<N>`.
5. Re-pins the consumer repo's lockfile to the new snapshot — `agents add @lendable/ai-agent-my-skill@pr<N>` runs automatically so the dist-tag re-resolves and the integrity SHA updates.

When the PR is merged, Changesets cuts a stable release; run `agents upgrade @lendable/ai-agent-my-skill` to move off the preview dist-tag.

**Conventions enforced by the command:**

- Slug must be **lowercase kebab-case** (`^[a-z0-9]+(?:-[a-z0-9]+)*$`). The CLI refuses uppercase, underscores, spaces, or path-traversal characters with a clear error at exit 2 — this is checked before any external call.
- Published name is always `@lendable/ai-agent-<slug>`. Other naming tiers (`@lendable/ai-cli-*` for CLI-ecosystem runbooks, `@lendable/ai-*` for general practice runbooks) are **not** published via this command — they still go through the manual monorepo PR flow. See [the authoring runbook](https://github.com/Lendable/ai/blob/main/packages/ai-cli-authoring-agent-packages/SKILL.md) for the naming tier matrix.
- The local draft is **whatever is currently at the kind's canonical `.agents/` path**, origin-agnostic. Works the same whether you hand-authored it from scratch or installed it via `agents add` and edited it locally.

**Requirements:**

- `gh` (GitHub CLI) installed and authenticated with push access to `Lendable/ai`. The command surfaces `gh exited <code>: <message>` verbatim if `gh` is missing or unauthorized.
- The CLI must be invoked from the root of the consumer repo (same as every other `agents` command).

**Iteration model (re-runs against the same slug):**

The same `agents publish my-skill` command does the right thing on every invocation:

- First run with no existing PR → opens a new PR.
- Subsequent runs with an open PR you own → appends a commit to its branch.
- Subsequent runs when the local tree is byte-identical to upstream → exits 0 with "no changes to publish".
- Subsequent runs when someone else owns the open PR for this slug → refuses with exit 3 and tells you to coordinate.

The `pr<N>` dist-tag stays the same across iterations; the underlying snapshot version changes. `agents add @lendable/ai-agent-my-skill@pr<N>` re-resolves the tag freshly on each call (no need to bump the pin manually).

## Don't

- **Don't `pnpm add @lendable/ai-agent-...` (or npm/yarn equivalent) directly.** The materialised `.agents/` won't update; the next CI `agents check` will fail. Use `agents add` instead.
- **Don't hand-edit files in `.agents/skills/<slug>/`, `.agents/agents/<slug>.md`, etc.** The CLI's manifest detects drift and the next sync may revert your edits.
- **Don't manually edit `.agents/lendable-sidecars/*.json`.** Those are the CLI's audit trail of what it placed into shared config files; tampering breaks the cleanup logic on the next remove/upgrade.
- **Don't commit `.agents/.lendable-cache/`** if it shows up. That directory is only created in non-JS-repo registry-fetch mode and is reproducible from `installed.json`. (In a JS repo with `package.json`, it's never created.)
- **Don't clone `Lendable/ai` to hand-craft `packages/ai-agent-<slug>/`.** That's what `agents publish` is for. The PR you'd open manually looks identical to the one the CLI produces, but the CLI also re-pins your consumer repo's lockfile to the new snapshot — saving you the follow-up `agents add @lendable/ai-agent-<slug>@pr<N>` step (and the inevitable forgetting of it).
- **Don't publish `@lendable/ai-cli-*` or `@lendable/ai-*` (non-`ai-agent-`) tier packages via `agents publish`.** Those tiers (runbooks about the CLI ecosystem, and general-Lendable-practice runbooks) intentionally go through the slower manual monorepo PR flow — they're curated, not flow-published. See the [naming convention](https://github.com/Lendable/ai/blob/main/packages/ai-cli-authoring-agent-packages/SKILL.md#naming-convention).

## Troubleshooting

- **`Unknown command: <cmd>`** — you typed something the CLI doesn't recognise. Run `npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents --help` for the full surface.
- **`Missing script: "@lendable/ai-agents-cli@latest"` or `ENOENT: package.json`** — you ran `npx @lendable/ai-agents-cli@latest <cmd>` instead of the canonical form above. npm 11's `npx` is broken for `@scope/pkg@tag` invocations (see "Why `npm exec ...`" above). Switch to `npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents <cmd>`.
- **`Cannot find module '@lendable/...'`** — you tried to import an agent-asset package as code. These are content packages (Markdown, JSON), not code; they're consumed by the CLI, not by `require()` / `import`. If you're debugging a missing dep, check `.agents/lendable-manifest.json` (or `.agents/.lendable-cache/installed.json` in registry-fetch mode) for what the CLI thinks is installed.
- **`agents check` fails after a manual `pnpm up @lendable/...` / `npm update @lendable/...` / `yarn upgrade @lendable/...`** — bumping the version in `package.json` directly doesn't re-materialise. Run `npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents sync` afterwards (or use `... agents upgrade` next time, which does both).
- **`agents lint` reports missing or invalid frontmatter** — edit the reported `SKILL.md` and add valid YAML frontmatter at the top. Every Skill needs at least `name: <slug>` and a description that says when to use it. If the reported name does not match the expected slug, either fix the `name` field or move/rename the package or materialised skill so they agree.
- **`agents add @lendable/foo@pr<N>` fails validation** — the current CLI's `safeNpmName` regex doesn't accept `@<tag>` suffixes in `add` args. Workaround until that lands: install via the consumer's pm directly (`pnpm add -D @lendable/foo@pr<N>` or equivalent), then run `npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents sync`.
- **`agents publish` exits 2 with "invalid slug: ..."** — your slug doesn't match the lowercase-kebab-case pattern. Rename the canonical `.agents/` path to use only `[a-z0-9-]` (no uppercase, no underscores, no traversal characters), then re-run.
- **`agents publish` exits 3 with "open PR #... is owned by @..."** — another engineer is iterating on the same slug in a PR you don't own. Coordinate with them; either wait for their PR to merge or pick a different slug.
- **`agents publish` exits 4 with a snapshot CI URL** — the `preview.yml` workflow failed for the pushed commit. Click the URL to see why (most commonly: lint/typecheck failure in the package contents, or the snapshot publish itself rejected by Artifactory). Fix the local files and re-run `agents publish <slug>` — the command is idempotent.
- **`agents publish` exits 5 with "repin exited ..."** — the snapshot published successfully but `agents add @lendable/ai-agent-<slug>@pr<N>` to update the lockfile failed locally. Re-run `npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents add @lendable/ai-agent-<slug>@pr<N>` by hand; the PR has the right version and the failure is just lockfile mechanics.
- **`agents` errors with "package age below minimum" / Aikido Safe Chain refusal** — the consumer has [Aikido Safe Chain](https://www.aikido.dev/safe-chain) installed, an npm wrapper that blocks packages younger than ~48 hours as a supply-chain mitigation. A freshly-published `@lendable/ai-agents-cli` release will be held back for the first ~48h. Durable fix: allowlist the `@lendable/*` scope so internal tooling is exempt — either `export SAFE_CHAIN_MINIMUM_PACKAGE_AGE_EXCLUSIONS="@lendable/*"` (per shell) or add `{ "npm": { "minimumPackageAgeExclusions": ["@lendable/*"] } }` to `~/.safe-chain/config.json` (machine-wide). One-shot bypass: append `--safe-chain-skip-minimum-package-age` to the `npm exec` invocation (recovery, not the recommended workflow).
