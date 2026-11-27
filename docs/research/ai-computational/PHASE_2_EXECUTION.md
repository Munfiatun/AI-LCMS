# Phase 2 — comparative experiment execution
Status at authoring: implementation submitted; regression and engine results require CI evidence. Phase 1 protocol and datasets are unchanged. The dedicated workflow runs only on the experiment branch; no other branch or existing workflow is modified.

## Run in a fresh isolated clone
Use PHP 8.4+, Composer, Node 22, Python 3.11+. Install locked dependencies, create a local disposable .env from .env.example, generate a disposable app key and clear config cache. Do not run in a deployment checkout or with a real database.
```sh
python3 scripts/research/ai_computational/generate_dataset.py --check
python3 scripts/research/fuzzy_reference_oracle.py
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL= php artisan test
export AI_COMPUTATIONAL_OUTPUT="$(mktemp -d)"
export AI_COMPUTATIONAL_COMMIT="$(git rev-parse HEAD)"
vendor/bin/phpunit -c phpunit.ai-computational.xml
```
The dedicated config forces SQLite :memory: and testing; the runner verifies the resolved connection before migration, refuses cached config, and rolls back each fixture and engine transaction. No assignments or persisted adaptive recommendations are created. Only synthetic fixture users are used. The test suite is explicitly invoked, not part of normal Unit/Feature tests.

## Outputs
comparative_results.csv: long format 2 rows per profile, 2,960 planned rows, D1–D4 only. run_manifest.json: actual invocation counts, failures, complete pairs, raw agreement counts per dataset, versions and SHA-256 hashes. D5 excluded; existing numerical reference remains separate. Fixture setup failures are recorded as two failed planned rows with no engine invocation. Error text deliberately sanitized; failures retained and CI fails if any call missing/failed. A partial CSV is not a completed experiment. Single-call timings are diagnostic only; controlled performance benchmarking is Phase 6. No artificial labels or expert validation.
Download artifact from Actions run; no results are committed by the workflow. Verify artifact hash, row count, actual calls and gate before using any result in article. The historical Phase 1 README/manifest are unchanged snapshots, not current run status. No claim of accuracy, learning effectiveness or superiority. Phase 3+ pending.
