#!/usr/bin/env python3
"""Independent, dependency-free numerical reference for AI-LCMS fuzzy-v2.

Frozen configuration copied from config/adaptive.php at R5 merge 0012cf7.
This is intentionally NOT a call into Laravel/FuzzyAdaptiveEngine. If the
algorithm config changes, update the reference, explain why, and version it.

Run: python3 scripts/research/fuzzy_reference_oracle.py
Use its printed table to cross-check PHPUnit crisp_output and fired_rules.
Numerical consistency != pedagogical validity.
"""

from __future__ import annotations

from itertools import product

INPUTS = {
    "mastery_score": {
        "low": (0, 0, 30, 50),
        "medium": (30, 60, 80),
        "high": (60, 80, 100, 100),
    },
    "evidence_count": {
        "low": (0, 0, 1, 2),
        "sufficient": (1, 3, 5),
        "high": (4, 5, 10, 10),
    },
    "prerequisite_mastery": {
        "not_mastered": (0, 0, 40, 60),
        "partial": (40, 65, 80),
        "mastered": (70, 85, 100, 100),
    },
}
OUTPUTS = {
    "additional_diagnostic": (0, 0, 10, 22),
    "prerequisite_remediation": (12, 28, 42),
    "remediation": (32, 45, 58),
    "reinforcement": (48, 60, 72),
    "next_concept": (64, 78, 90),
    "enrichment": (82, 92, 100, 100),
}
RULES = [
    ({"evidence_count": "low"}, "additional_diagnostic"),
    ({"evidence_count": "sufficient", "prerequisite_mastery": "not_mastered"}, "prerequisite_remediation"),
    ({"evidence_count": "high", "prerequisite_mastery": "not_mastered"}, "prerequisite_remediation"),
]
for mastery_label in ("low", "medium", "high"):
    for prerequisite_label in ("partial", "mastered"):
        for evidence_label in ("sufficient", "high"):
            if mastery_label == "low":
                output = "remediation"
            elif mastery_label == "medium" or prerequisite_label == "partial":
                output = "reinforcement"
            elif evidence_label == "sufficient":
                output = "next_concept"
            else:
                output = "enrichment"
            RULES.append((
                {
                    "mastery_score": mastery_label,
                    "evidence_count": evidence_label,
                    "prerequisite_mastery": prerequisite_label,
                },
                output,
            ))


def membership(value: float, points: tuple[int, ...]) -> float:
    """Independent geometric definition for triangular / trapezoid membership."""
    if len(points) == 3:
        left, apex, right = points
        if value <= left or value >= right:
            return 1.0 if value == apex else 0.0
        if value <= apex:
            return (value - left) / (apex - left)
        return (right - value) / (right - apex)

    left, rising_end, falling_start, right = points
    if value < left or value > right:
        return 0.0
    if rising_end <= value <= falling_start:
        return 1.0
    if value < rising_end:
        return 1.0 if rising_end == left else (value - left) / (rising_end - left)
    return 1.0 if right == falling_start else (right - value) / (right - falling_start)


def evaluate(mastery: float, evidence: int, prerequisite: float) -> tuple[float, str, int]:
    values = {
        "mastery_score": max(0.0, min(100.0, mastery)),
        "evidence_count": max(0.0, min(10.0, float(evidence))),
        "prerequisite_mastery": max(0.0, min(100.0, prerequisite)),
    }
    fuzzy_inputs = {
        input_name: {
            label: round(membership(values[input_name], shape), 6)
            for label, shape in sets.items()
        }
        for input_name, sets in INPUTS.items()
    }
    activations = {name: 0.0 for name in OUTPUTS}
    fired = 0
    for conditions, consequence in RULES:
        strength = min(fuzzy_inputs[input_name][label] for input_name, label in conditions.items())
        activations[consequence] = max(activations[consequence], strength)
        fired += int(strength > 0.0)

    numerator = denominator = 0.0
    for x in range(101):
        aggregate = max(
            min(activation, membership(float(x), OUTPUTS[name]))
            for name, activation in activations.items()
        )
        numerator += x * aggregate
        denominator += aggregate
    centroid = numerator / denominator if denominator else 0.0

    # Output label is based on membership at the crisp position, not solely
    # on firing strength. It can differ from final resolved learning action.
    memberships_at_centroid = {
        name: membership(centroid, points) for name, points in OUTPUTS.items()
    }
    label = max(memberships_at_centroid, key=memberships_at_centroid.get)
    if memberships_at_centroid[label] <= 0.0:
        best_activation = max(activations.values())
        label = next(
            (name for name, strength in activations.items() if strength == best_activation and strength > 0),
            "additional_diagnostic",
        )
    return round(centroid, 4), label, fired


REFERENCE = (
    (20, 3, 100, 45.0000, "remediation"),
    (40, 3, 100, 50.8528, "remediation"),
    (60, 3, 100, 60.0000, "reinforcement"),
    (80, 3, 100, 77.3333, "next_concept"),
    (90, 1, 100, 8.1111, "additional_diagnostic"),
    (90, 4, 100, 77.2222, "next_concept"),
    (90, 5, 100, 93.4444, "enrichment"),
    (90, 3, 30, 27.3333, "prerequisite_remediation"),
)


def main() -> None:
    assert len(RULES) == 15
    print("mastery,evidence,prerequisite,centroid,selected_output,fired_rules")
    for mastery, evidence, prerequisite, expected, expected_label in REFERENCE:
        crisp, label, fired = evaluate(mastery, evidence, prerequisite)
        assert abs(crisp - expected) <= 0.0001, (mastery, evidence, prerequisite, crisp, expected)
        assert label == expected_label, (mastery, evidence, prerequisite, label, expected_label)
        assert fired > 0
        print(f"{mastery},{evidence},{prerequisite},{crisp:.4f},{label},{fired}")

    count = 0
    for mastery, evidence, prerequisite in product(
        (0, 20, 40, 60, 80, 100),
        (0, 1, 2, 3, 4, 5, 10, 11),
        (0, 30, 40, 60, 70, 80, 100),
    ):
        crisp, _, fired = evaluate(mastery, evidence, prerequisite)
        assert fired > 0, (mastery, evidence, prerequisite, "no rule fired")
        assert 0.0 <= crisp <= 100.0
        count += 1
    print(f"REFERENCE OK: {len(REFERENCE)} centroid cases; {count} grid states with nonzero rule coverage")


if __name__ == "__main__":
    main()
