#!/usr/bin/env python3
"""Aggregate anonymized ISO quality-in-use pilot task observations.

Compares evidence mappings, NOT numerical ISO certification scores:
- ISO/IEC 25010:2011 quality-in-use (five characteristics)
- ISO/IEC 25019:2023 quality-in-use (three characteristics)

No external libraries, no raw participant IDs in output, and no imputation.
"""
from __future__ import annotations

import argparse
import csv
import json
import re
import statistics
import sys
from collections import Counter, defaultdict
from pathlib import Path

REQUIRED = {
    "participant_id", "role", "condition", "session_id", "task_id",
    "context_id", "app_sha", "completed", "time_seconds", "errors",
    "assistance_count", "satisfaction_1to5", "accessibility_1to5",
    "suitability_1to5", "experience_1to5", "trust_1to5",
    "risk_severity", "risk_type",
}
RATINGS = (
    "satisfaction_1to5", "accessibility_1to5", "suitability_1to5",
    "experience_1to5", "trust_1to5",
)
SEVERITIES = ("none", "low", "medium", "high", "critical")
ROLES = ("instructor", "student")
CONDITIONS = ("adaptive", "non_adaptive", "none")
SUCCESS = ("0", "1", "na")
ID_PATTERN = re.compile(r"^[A-Za-z][A-Za-z0-9_-]{1,20}$")
SHA_PATTERN = re.compile(r"^[a-f0-9]{7,40}$")


def read_csv(path: Path) -> list[dict[str, str]]:
    with path.open("r", encoding="utf-8-sig", newline="") as stream:
        reader = csv.DictReader(stream)
        if reader.fieldnames is None:
            raise ValueError(f"{path.name}: CSV header missing")
        if len(reader.fieldnames) != len(set(reader.fieldnames)):
            raise ValueError(f"{path.name}: duplicate CSV columns")
        rows = []
        for row in reader:
            if None in row:
                raise ValueError(f"{path.name}: extra unnamed columns")
            rows.append({key: (value or "").strip() for key, value in row.items()})
        return rows


def parse_rating(raw: str, field: str, line: int) -> int | None:
    if raw == "":
        return None
    if raw not in ("1", "2", "3", "4", "5"):
        raise ValueError(f"line {line}: {field} must be blank or integer 1..5")
    return int(raw)


def parse_nonnegative(raw: str, field: str, line: int) -> float | None:
    if raw == "":
        return None
    try:
        value = float(raw)
    except ValueError as exc:
        raise ValueError(f"line {line}: invalid {field}") from exc
    if not (0 <= value < 1e7):
        raise ValueError(f"line {line}: {field} must be finite and nonnegative")
    return value


def validate(rows: list[dict[str, str]]) -> list[dict]:
    if not rows:
        raise ValueError("No observations yet. Do not report an ISO result from a blank template.")
    missing = REQUIRED - set(rows[0])
    if missing:
        raise ValueError(f"Missing required columns: {', '.join(sorted(missing))}")
    normalized = []
    keys = set()
    sha_versions = set()
    for line, row in enumerate(rows, 2):
        if row["role"] not in ROLES:
            raise ValueError(f"line {line}: unknown role")
        if row["condition"] not in CONDITIONS:
            raise ValueError(f"line {line}: unknown condition")
        if row["completed"] not in SUCCESS:
            raise ValueError(f"line {line}: completed must be 0, 1, or na")
        if row["risk_severity"] not in SEVERITIES:
            raise ValueError(f"line {line}: unknown risk severity")
        if not ID_PATTERN.fullmatch(row["participant_id"]):
            raise ValueError(f"line {line}: participant_id must be an anonymous short code")
        if not ID_PATTERN.fullmatch(row["session_id"]):
            raise ValueError(f"line {line}: session_id must be an anonymous short code")
        if not ID_PATTERN.fullmatch(row["task_id"]):
            raise ValueError(f"line {line}: invalid task_id")
        if not ID_PATTERN.fullmatch(row["context_id"]):
            raise ValueError(f"line {line}: invalid context_id")
        if not SHA_PATTERN.fullmatch(row["app_sha"]):
            raise ValueError(f"line {line}: app_sha must be a real 7..40 digit hex commit")
        sha_versions.add(row["app_sha"])

        key = (
            row["participant_id"], row["session_id"],
            row["task_id"], row["context_id"],
        )
        if key in keys:
            raise ValueError(f"line {line}: duplicate participant/session/task/context")
        keys.add(key)

        time = parse_nonnegative(row["time_seconds"], "time_seconds", line)
        errors = parse_nonnegative(row["errors"], "errors", line)
        assistance = parse_nonnegative(row["assistance_count"], "assistance_count", line)
        if errors is not None and not errors.is_integer():
            raise ValueError(f"line {line}: errors must be an integer")
        if assistance is not None and not assistance.is_integer():
            raise ValueError(f"line {line}: assistance_count must be an integer")
        if row["completed"] != "na" and time is None:
            raise ValueError(f"line {line}: a valid attempt requires a measured time_seconds")
        ratings = {field: parse_rating(row[field], field, line) for field in RATINGS}
        normalized.append({
            **row, "time_seconds": time,
            "errors": int(errors) if errors is not None else None,
            "assistance_count": int(assistance) if assistance is not None else None,
            **ratings,
        })
    if len(sha_versions) != 1:
        raise ValueError("Multiple app_sha values: separate versions before analysis")
    return normalized


def median_or_none(numbers: list[float | int]) -> float | None:
    return round(float(statistics.median(numbers)), 2) if numbers else None


def summarize(rows: list[dict]) -> dict:
    eligible = [row for row in rows if row["completed"] != "na"]
    counts = Counter(row["risk_severity"] for row in eligible)
    time = [row["time_seconds"] for row in eligible if row["time_seconds"] is not None]
    completed = sum(row["completed"] == "1" for row in eligible)
    result = {
        "distinct_participants": len(set(row["participant_id"] for row in rows)),
        "observations_total": len(rows),
        "valid_tasks": len(eligible),
        "not_applicable": len(rows) - len(eligible),
        "successful_tasks": completed,
        "task_success_pct": round(100 * completed / len(eligible), 2) if eligible else None,
        "median_time_seconds": median_or_none(time),
        "error_total": sum(row["errors"] or 0 for row in eligible),
        "assistance_total": sum(row["assistance_count"] or 0 for row in eligible),
        "risk_events_by_severity": {level: counts.get(level, 0) for level in SEVERITIES if level != "none"},
        "rating_medians": {},
        "rating_response_n": {},
    }
    for field in RATINGS:
        values = [row[field] for row in eligible if row[field] is not None]
        result["rating_medians"][field] = median_or_none(values)
        result["rating_response_n"][field] = len(values)
    return result


def coverage(rows: list[dict], contexts_path: Path | None) -> dict | None:
    if contexts_path is None:
        return None
    matrix = read_csv(contexts_path)
    required_columns = {"context_id", "role", "task_id", "required_before_pilot"}
    if not matrix:
        raise ValueError("The context matrix must contain planned context/task combinations")
    if required_columns - set(matrix[0]):
        raise ValueError("Context matrix missing required columns")
    required = set()
    for line, item in enumerate(matrix, 2):
        if item["required_before_pilot"].lower() not in {"yes", "no"}:
            raise ValueError(f"context matrix line {line}: required_before_pilot must be yes/no")
        if item["required_before_pilot"].lower() == "yes":
            required.add((item["role"], item["task_id"], item["context_id"]))
    observed = {(r["role"], r["task_id"], r["context_id"])
                for r in rows if r["completed"] != "na"}
    success = {(r["role"], r["task_id"], r["context_id"])
               for r in rows if r["completed"] == "1"}
    return {
        "required_context_task_combinations": len(required),
        "observed_combinations": len(required & observed),
        "successfully_completed_combinations": len(required & success),
        "not_observed_count": len(required - observed),
        "coverage_observed_pct": (
            round(100 * len(required & observed) / len(required), 2) if required else None
        ),
        "coverage_success_pct": (
            round(100 * len(required & success) / len(required), 2) if required else None
        ),
    }


def generate_report(rows: list[dict], contexts_path: Path | None = None) -> dict:
    validated = validate(rows)
    all_stats = summarize(validated)
    coverage_stats = coverage(validated, contexts_path)

    def group_by(field: str) -> dict:
        groups = defaultdict(list)
        for item in validated:
            groups[item[field]].append(item)
        return {key: summarize(items) for key, items in sorted(groups.items())}

    return {
        "report_kind": "descriptive_quality_in_use_pilot_not_iso_certification",
        "app_sha": validated[0]["app_sha"],
        "n_participants": all_stats["distinct_participants"],
        "all_tasks": all_stats,
        "by_role": group_by("role"),
        "by_task": group_by("task_id"),
        "by_context": group_by("context_id"),
        "iso_25010_2011": {
            "effectiveness": {"task_success_pct": all_stats["task_success_pct"]},
            "efficiency": {
                "median_time_seconds": all_stats["median_time_seconds"],
                "error_total": all_stats["error_total"],
                "assistance_total": all_stats["assistance_total"],
            },
            "satisfaction": {
                "median_rating_1to5": all_stats["rating_medians"]["satisfaction_1to5"],
                "answered_n": all_stats["rating_response_n"]["satisfaction_1to5"],
            },
            "freedom_from_risk": all_stats["risk_events_by_severity"],
            "context_coverage": coverage_stats,
        },
        "iso_25019_2023": {
            "beneficialness": {
                "usability": {
                    "task_success_pct": all_stats["task_success_pct"],
                    "median_time_seconds": all_stats["median_time_seconds"],
                    "median_satisfaction_1to5": all_stats["rating_medians"]["satisfaction_1to5"],
                },
                "accessibility": {
                    "median_rating_1to5": all_stats["rating_medians"]["accessibility_1to5"],
                    "answered_n": all_stats["rating_response_n"]["accessibility_1to5"],
                },
                "suitability": {
                    "median_rating_1to5": all_stats["rating_medians"]["suitability_1to5"],
                    "answered_n": all_stats["rating_response_n"]["suitability_1to5"],
                },
            },
            "freedom_from_risk": all_stats["risk_events_by_severity"],
            "acceptability": {
                "experience_median_1to5": all_stats["rating_medians"]["experience_1to5"],
                "trust_median_1to5": all_stats["rating_medians"]["trust_1to5"],
                "governance_compliance": "SEPARATE REVIEWER CHECKLIST REQUIRED",
            },
        },
        "limitations": [
            "2011 and 2023 characteristics are not interchangeable or summed into a single ISO score",
            "Direct-user ratings do not establish actual absence of risk or compliance",
            "No causal learning-effectiveness inference; report only observed task use",
            "No context-coverage inference without an explicitly specified context matrix",
        ],
    }


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("observations", type=Path, help="pseudonymous task CSV")
    parser.add_argument("--contexts", type=Path, default=None, help="prespecified context matrix CSV")
    parser.add_argument("--output", type=Path, default=None, help="aggregated JSON (no raw participant IDs)")
    args = parser.parse_args()
    try:
        report = generate_report(read_csv(args.observations), args.contexts)
        output = json.dumps(report, ensure_ascii=False, indent=2, sort_keys=True) + "\n"
        if args.output:
            args.output.write_text(output, encoding="utf-8")
        else:
            print(output, end="")
        return 0
    except (ValueError, OSError) as exc:
        print(f"Quality-in-use report rejected: {exc}", file=sys.stderr)
        return 2


if __name__ == "__main__":
    sys.exit(main())
