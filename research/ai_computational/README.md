# Computational evaluation inputs — Phase 0/1
Baseline: 8d99545be7039e97cc3778b356276efe4f5f1281. Protocol: ../../docs/research/ai-computational/RESEARCH_PROTOCOL.md (from repository root: docs/research/ai-computational/RESEARCH_PROTOCOL.md). No new engine tests or pedagogical results.
From repo root, Python 3.11+ standard library:
```sh
python3 scripts/research/ai_computational/generate_dataset.py --check
python3 scripts/research/ai_computational/generate_dataset.py --output /tmp/ai-lcms-synthetic
```
The checked-in CSVs are input only. Manifest records counts and FNV-1a checksums (not cryptographic). Re-run byte comparison before Phase 2. Historical CI is not a fresh baseline regression. No database use in generator. Phase 2+ not implemented.
