from pathlib import Path
import sys

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT))

from tools.rail.inspect_consist_workbooks import inspect  # noqa: E402


def main() -> int:
    references = ROOT / "docs" / "rail" / "consist" / "referencias"
    template = inspect(references / "Plantilla_Consist.xlsm")
    official = inspect(references / "Consist Rail del 29 al 30 de Julio de 2026.xlsx")
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

    assert official["sha256"] == "50d3557f9c0c95f5d8555f7b373fa424ef7544d27a5b276ad400b2d692ffa4d6"
    assert [sheet["name"] for sheet in official["sheets"]] == ["Consist", "Summary", "Version"]
    assert [sheet["state"] for sheet in official["sheets"]] == ["visible", "visible", "hidden"]
    assert official["sheets"][0]["column_widths"]["1:1"] == "29.42578125"
    assert official["sheets"][1]["column_widths"]["1:1"] == "33.140625"
    assert official["sheets"][2]["column_widths"]["2:2"] == "68"
    assert official["tables"][0]["ref"] == "A1:N438"
    assert official["tables"][1]["ref"] == "A1:G56"
    assert official["sheets"][0]["formula_count"] == 0
    assert official["sheets"][1]["formula_count"] == 0

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
