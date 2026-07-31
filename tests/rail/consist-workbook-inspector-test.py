from pathlib import Path
import sys

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT))

from tools.rail.inspect_consist_workbooks import inspect  # noqa: E402


def main() -> int:
    references = ROOT / "docs" / "rail" / "consist" / "referencias"
    template = inspect(references / "Plantilla_Consist.xlsm")
    master = inspect(references / "vascor_sm_db.xlsx")

    assert template["zip_valid"] is True
    assert template["sha256"] == "6a1ec3f03a0ca42374e3764771f80b2afcb78addaa400a05da4386f841ced5cb"
    assert [sheet["name"] for sheet in template["sheets"]] == [
        "Consist",
        "Summary",
        "  Vehicle Load Report",
        "Shippers",
        "CNACS",
        "Version",
    ]
    assert template["parts"]["vba"] is True
    assert len(template["parts"]["pivot_tables"]) == 16

    assert master["zip_valid"] is True
    assert master["sha256"] == "42640d6baf5de85a151c38ea6d1234d5f8a0948bb605e2e173d7a60b5bf9972e"
    assert [sheet["name"] for sheet in master["sheets"]] == [
        "route_codes",
        "route_codes_truck",
        "route_codes_tren",
        "production_status",
        "No Activas",
        "v28052026",
    ]
    assert master["parts"]["vba"] is False
    print("[PASS] Consist workbook inspector")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
