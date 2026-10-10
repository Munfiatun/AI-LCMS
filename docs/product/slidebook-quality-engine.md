# Slidebook Quality Engine

R2 adds a deterministic quality check before teacher approval. The engine is intentionally advisory for recoverable issues and blocking only for clearly unsafe presentation quality.

## Quality statuses

- **Siap Ditinjau** — no detected quality issues.
- **Perlu Perhatian** — warnings exist, but the teacher may still review and approve.
- **Perlu Diperbaiki** — at least one critical issue must be fixed before approval.

The score starts at 100. A warning deducts 3 points and a critical issue deducts 12 points. The score helps prioritize review; approval is blocked by critical issues, not by a score threshold alone.

## Audience-aware density

| Audience | Text warning | Text blocker | Point warning | Point blocker |
| --- | ---: | ---: | ---: | ---: |
| SMP | > 90 words | > 140 words | > 4 points | > 7 points |
| SMA/SMK | > 130 words | > 190 words | > 5 points | > 8 points |
| General | > 150 words | > 220 words | > 6 points | > 9 points |

For SMP, unusually long sentence structure is also surfaced as a warning.

## Additional checks

The engine checks title length, empty content, unresolved teacher review flags, layout variety, source-reference coverage, conservative source-term overlap, and opportunities to use process/comparison/code/data-oriented visual layouts.

Missing source-reference metadata on an older Slidebook is warning-only for backward compatibility. Newly generated Slidebooks should keep source references so teachers can trace learning content back to its material.

## Human review remains authoritative

Quality checks do not verify pedagogical truth or replace the teacher. The teacher remains responsible for checking meaning, factual accuracy, learning suitability, and final publication.
