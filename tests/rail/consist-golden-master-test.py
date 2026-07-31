import json
from pathlib import Path
import sys
import tempfile

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / "tools" / "rail"))

from build_consist_golden_master import build  # noqa: E402


def main() -> int:
    references = ROOT / "docs" / "rail" / "consist" / "referencias"
    with tempfile.TemporaryDirectory(prefix="consist-golden-") as temporary:
        output = Path(temporary)
        result = build(
            references / "Plantilla_Consist.xlsm",
            references / "vascor_sm_db.xlsx",
            output,
        )
        assert result["vehicle_load_unique"] == 624
        assert result["shippers_unique"] == 624
        assert result["cnacs_records"] == 648
        assert result["cnacs_unique"] == 624
        assert result["cnacs_duplicate_cases"] == 24
        assert result["consist_rows"] == 624
        assert result["consist_unique"] == 624
        assert result["consist_vins_subset_of_inputs"] is True
        assert result["summary_rows"] == 472
        assert result["summary_unique_platforms"] == 59
        assert result["summary_ut_total_unique_platforms"] == 472
        assert result["summary_difference_against_consist"] == 152
        catalog = json.loads((output / "catalog-resolution.json").read_text(encoding="utf-8"))
        assert catalog["rows_matching_current_master_physical_c_d"] == 624
        duplicates = json.loads((output / "duplicate-analysis.json").read_text(encoding="utf-8"))
        assert duplicates["case_count"] == 24
        assert all(case["different_columns"] == ["NumRemesa"] for case in duplicates["cases"])
    print("[PASS] Consist golden master")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
