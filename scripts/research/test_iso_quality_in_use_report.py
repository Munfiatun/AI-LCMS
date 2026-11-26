"""No-user-data regression checks for ISO quality-in-use descriptive report."""
import copy
import json
import sys
import tempfile
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from iso_quality_in_use_report import generate_report


SHA = "aab4fe160b4a51f094d1493d217507cbf88869f2"


def observation(**overrides):
    base = {
        "participant_id": "G01",
        "role": "instructor",
        "condition": "adaptive",
        "session_id": "TEST01",
        "task_id": "G01",
        "context_id": "DESK",
        "app_sha": SHA,
        "completed": "1",
        "time_seconds": "60",
        "errors": "0",
        "assistance_count": "0",
        "satisfaction_1to5": "4",
        "accessibility_1to5": "5",
        "suitability_1to5": "4",
        "experience_1to5": "4",
        "trust_1to5": "3",
        "risk_severity": "none",
        "risk_type": "",
    }
    base.update(overrides)
    return base


class ISOQualityInUseReportTest(unittest.TestCase):
    def test_maps_metrics_to_distinct_2011_and_2023_models(self):
        rows = [
            observation(),
            observation(
                participant_id="S01", role="student", condition="non_adaptive",
                task_id="S01", context_id="PHONE", completed="0",
                time_seconds="120", errors="2", assistance_count="1",
                satisfaction_1to5="2", accessibility_1to5="2",
                experience_1to5="2", trust_1to5="2",
                risk_severity="low", risk_type="usability",
            ),
        ]
        report = generate_report(rows)
        self.assertEqual(report["all_tasks"]["task_success_pct"], 50.0)
        self.assertEqual(report["all_tasks"]["median_time_seconds"], 90)
        self.assertEqual(report["iso_25010_2011"]["satisfaction"]["median_rating_1to5"], 3)
        self.assertEqual(report["iso_25019_2023"]["beneficialness"]["accessibility"]["median_rating_1to5"], 3.5)
        self.assertEqual(report["iso_25019_2023"]["freedom_from_risk"]["low"], 1)
        self.assertEqual(report["by_role"]["student"]["successful_tasks"], 0)
        self.assertNotIn("S01", json.dumps(report))
        self.assertNotIn("G01", json.dumps(report))

    def test_calculates_only_prespecified_context_coverage(self):
        rows = [observation()]
        with tempfile.TemporaryDirectory() as directory:
            path = Path(directory) / "contexts.csv"
            path.write_text(
                "context_id,role,task_id,required_before_pilot\n"
                "DESK,instructor,G01,yes\n"
                "PHONE,student,S01,yes\n"
                "OTHER,student,S02,no\n", encoding="utf-8"
            )
            report = generate_report(rows, path)
        coverage = report["iso_25010_2011"]["context_coverage"]
        self.assertEqual(coverage["required_context_task_combinations"], 2)
        self.assertEqual(coverage["observed_combinations"], 1)
        self.assertEqual(coverage["not_observed_count"], 1)
        self.assertEqual(coverage["coverage_observed_pct"], 50)

    def test_rejects_empty_data_and_bad_ratings(self):
        with self.assertRaisesRegex(ValueError, "No observations"):
            generate_report([])
        with self.assertRaisesRegex(ValueError, "must be blank or integer"):
            generate_report([observation(satisfaction_1to5="9")])

    def test_rejects_mixed_software_versions_and_duplicate_attempts(self):
        with self.assertRaisesRegex(ValueError, "Multiple app_sha"):
            generate_report([
                observation(),
                observation(
                    participant_id="S01", session_id="TEST02",
                    app_sha="b" * 40
                ),
            ])
        with self.assertRaisesRegex(ValueError, "duplicate"):
            generate_report([observation(), copy.deepcopy(observation())])

    def test_missing_rating_is_not_imputed_as_zero(self):
        report = generate_report([observation(satisfaction_1to5="")])
        self.assertIsNone(report["iso_25010_2011"]["satisfaction"]["median_rating_1to5"])
        self.assertEqual(report["iso_25010_2011"]["satisfaction"]["answered_n"], 0)


if __name__ == "__main__":
    unittest.main()
