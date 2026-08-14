# Lendable JSON Serializer

Provides an opinionated object-oriented interface for handling JSON serialization and deserialization in PHP. It is deliberately restrictive rather than a generic solution: it fits the `data array(s) <=> json` part of the flow where object graphs are converted to data arrays (and back) elsewhere in the calling code.

- `Serializer::serialize(array $data): string` — serializes a data array into a JSON string. Throws `SerializationFailed` on failure.
- `Serializer::deserialize(string $json): array` — deserializes a JSON string into an array. Throws `DeserializationFailed` on failure to deserialize, and `InvalidDeserializedData` if the resulting data is not an array (e.g. root is a scalar).

Unlike raw `json_encode()`/`json_decode()`, this library always throws on failure instead of returning `false` or requiring `json_last_error()` checks, and applies sane default serialization flags.

## Layout

- `src/` — library source, PSR-4 autoloaded under `Lendable\Json\`.
  - `Serializer.php` — the main API.
  - `SerializationFailed.php`, `DeserializationFailed.php`, `InvalidDeserializedData.php`, `Failure.php` — exception types.
- `tests/unit/` — PHPUnit unit tests, autoloaded under `Tests\Lendable\Json\Unit\`.

## Requirements

PHP `^8.4`. CI additionally runs against PHP 8.5, and against lowest/highest/locked Composer dependency sets.

## Common commands

Run via Composer:

```bash
composer lint                 # parallel-lint over src/ and tests/
composer phpstan              # PHPStan (max strictness — phpstan-strict-rules, phpstan-deprecation-rules, phpstan-phpunit)
composer code-style:check     # php-cs-fixer dry-run
composer code-style:fix       # php-cs-fixer, applies fixes
composer rector:check         # Rector dry-run
composer rector:fix           # Rector, applies fixes
composer phpunit:unit         # PHPUnit unit test suite
composer infection             # Infection mutation testing (--min-msi=100 --min-covered-msi=100)
composer tests:unit            # phpunit:unit + infection
composer static-analysis       # composer validate + lint + phpstan + rector:check
composer ci                    # static-analysis + code-style:check + tests:unit — mirrors the CI pipeline
```

Infection requires 100% mutation score (`--min-msi=100 --min-covered-msi=100`), so new code needs tests that actually kill mutants, not just line coverage.

## Conventions

- PR titles must follow Conventional Commits (validated by `.github/commitlint.config.js`): lower-case `type`, non-empty `scope`, no trailing period on the subject. Allowed types: `build`, `chore`, `ci`, `docs`, `feat`, `fix`, `perf`, `refactor`, `revert`, `style`, `test`.
- Releases are automated via release-please on `master`/`releases/**` — don't hand-edit `CHANGELOG.md` or version tags.

## Consuming shared agent assets

Shared agent assets (skills, agents, commands, hooks, MCP servers) live under `.agents/` and are managed by the Lendable agents CLI ([`@lendable/ai-agents-cli`](https://jfrog.shared.prod.zable.co.uk/artifactory/api/npm/npm/@lendable/ai-agents-cli)). Per-harness directories (`.claude/`, `.codex/`, `.cursor/`) contain only CLI-managed symlinks and sidecar config — never edit them by hand.

Install a shared `@lendable/ai-agent-*` package:

```bash
npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents add <package-name>
```

Verify the working tree matches `.agents/lendable-manifest.json` (this is what CI runs):

```bash
npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents check
```

Validate skill frontmatter and get recommendations:

```bash
npm exec --yes --package=@lendable/ai-agents-cli@latest -- agents lint --source=all
```

If you hit HTTP 401/403 fetching from the registry, this machine isn't authenticated to Lendable's Artifactory yet. Open the Getting Started guide and copy the Bootstrap prompt from there: https://automatic-sniffle-qjwj75e.pages.github.io/packages/ai-agents-cli/getting-started/
