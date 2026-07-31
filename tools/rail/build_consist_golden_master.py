#!/usr/bin/env python3
"""Construye evidencia golden master desde una plantilla Consist, sin recalcularla."""

from __future__ import annotations

import argparse
import hashlib
import json
import re
import zipfile
from collections import Counter, defaultdict
from pathlib import Path
from xml.etree import ElementTree as ET

from inspect_consist_workbooks import NS, cell_value, col_number, shared_strings, xml

BUILTIN_FORMATS = {
    0: "General", 1: "0", 2: "0.00", 9: "0%", 10: "0.00%",
    14: "mm-dd-yy", 49: "@",
}


def write_json(path: Path, payload: object) -> None:
    path.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")


def workbook_sheets(book: zipfile.ZipFile) -> dict[str, str]:
    root = xml(book, "xl/workbook.xml")
    rels = xml(book, "xl/_rels/workbook.xml.rels")
    targets = {}
    if rels is not None:
        for rel in rels:
            targets[rel.get("Id")] = rel.get("Target", "")
    result = {}
    for sheet in root.findall("m:sheets/m:sheet", NS):
        rid = sheet.get(f"{{{NS['r']}}}id")
        target = targets[rid].replace("\\", "/").lstrip("/")
        result[sheet.get("name", "")] = target if target.startswith("xl/") else f"xl/{target}"
    return result


def style_formats(book: zipfile.ZipFile) -> list[str]:
    root = xml(book, "xl/styles.xml")
    custom = {}
    if root is not None:
        for item in root.findall("m:numFmts/m:numFmt", NS):
            custom[int(item.get("numFmtId", "0"))] = item.get("formatCode", "General")
    result = []
    if root is not None:
        for xf in root.findall("m:cellXfs/m:xf", NS):
            num_id = int(xf.get("numFmtId", "0"))
            result.append(custom.get(num_id, BUILTIN_FORMATS.get(num_id, f"builtin:{num_id}")))
    return result


def parse_sheet(book: zipfile.ZipFile, path: str, strings: list[str], formats: list[str]) -> dict:
    root = xml(book, path)
    rows = []
    row_meta = {}
    for row in root.findall("m:sheetData/m:row", NS):
        row_number = int(row.get("r", "0"))
        row_meta[row_number] = {
            "hidden": row.get("hidden") == "1",
            "outline_level": int(row.get("outlineLevel", "0")),
        }
        cells = {}
        for cell in row.findall("m:c", NS):
            coordinate = cell.get("r", "")
            column = col_number(coordinate)
            style_id = int(cell.get("s", "0"))
            cells[column] = {
                "coordinate": coordinate,
                "formula": cell.findtext("m:f", default=None, namespaces=NS),
                "cached_value": cell_value(cell, strings),
                "data_type": cell.get("t", "n"),
                "style_id": style_id,
                "visible_format": formats[style_id] if style_id < len(formats) else "General",
            }
        rows.append((row_number, cells))
    hidden_columns = []
    for col in root.findall("m:cols/m:col", NS):
        if col.get("hidden") == "1":
            hidden_columns.append({
                "min": int(col.get("min", "0")),
                "max": int(col.get("max", "0")),
                "outline_level": int(col.get("outlineLevel", "0")),
            })
    merges = [item.get("ref") for item in root.findall("m:mergeCells/m:mergeCell", NS)]
    return {
        "rows": rows,
        "row_meta": row_meta,
        "hidden_columns": hidden_columns,
        "merged_cells": merges,
    }


def normalized(value: object) -> str:
    return str(value or "").strip().upper()


def cell_payload(cell: dict | None, header: str) -> dict:
    if cell is None:
        return {
            "coordinate": None, "header": header, "formula": None,
            "cached_value": None, "data_type": None, "style_id": None,
            "visible_format": None,
        }
    return {"header": header, **cell}


def rows_by_key(sheet: dict, column: int) -> tuple[list[dict], dict[str, list[tuple[int, dict]]]]:
    values = []
    index: dict[str, list[tuple[int, dict]]] = defaultdict(list)
    for row_number, cells in sheet["rows"][1:]:
        key = normalized((cells.get(column) or {}).get("cached_value"))
        if key:
            values.append({"row": row_number, "key": key, "cells": cells})
            index[key].append((row_number, cells))
    return values, index


def build(template_path: Path, master_path: Path, output: Path) -> dict:
    output.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(template_path) as template, zipfile.ZipFile(master_path) as master:
        template_strings = shared_strings(template)
        template_formats = style_formats(template)
        template_paths = workbook_sheets(template)
        master_strings = shared_strings(master)
        master_formats = style_formats(master)
        master_paths = workbook_sheets(master)

        source_specs = {
            "vehicle_load_report": ("  Vehicle Load Report", 2),
            "shippers": ("Shippers", 1),
            "cnacs": ("CNACS", 1),
        }
        sources = {}
        input_summary = {}
        for key, (name, vin_col) in source_specs.items():
            sheet = parse_sheet(template, template_paths[name], template_strings, template_formats)
            records, index = rows_by_key(sheet, vin_col)
            sources[key] = {"sheet": sheet, "records": records, "index": index}
            input_summary[key] = {
                "sheet": name,
                "records_with_vin": len(records),
                "unique_vins": len(index),
                "duplicate_occurrences": sum(len(rows) - 1 for rows in index.values()),
                "duplicate_vins": sum(1 for rows in index.values() if len(rows) > 1),
                "first_data_row": min((item["row"] for item in records), default=None),
                "last_data_row": max((item["row"] for item in records), default=None),
            }
        write_json(output / "inputs-summary.json", input_summary)

        consist_sheet = parse_sheet(template, template_paths["Consist"], template_strings, template_formats)
        consist_headers = {
            column: str(cell["cached_value"] or "")
            for column, cell in consist_sheet["rows"][0][1].items() if column <= 15
        }
        consist_rows = []
        for row_number, cells in consist_sheet["rows"][1:]:
            vin = normalized((cells.get(2) or {}).get("cached_value"))
            if not vin:
                continue
            consist_rows.append({
                "position": len(consist_rows) + 1,
                "row": row_number,
                "vin": vin,
                "cells": [
                    cell_payload(cells.get(column), consist_headers.get(column, ""))
                    for column in range(1, 16)
                ],
            })
        consist_output = {
            "sheet": "Consist",
            "row_count": len(consist_rows),
            "unique_vins": len({row["vin"] for row in consist_rows}),
            "merged_cells": consist_sheet["merged_cells"],
            "hidden_columns": consist_sheet["hidden_columns"],
            "hidden_rows": [
                row for row, meta in consist_sheet["row_meta"].items() if meta["hidden"]
            ],
            "rows": consist_rows,
        }
        write_json(output / "consist-output.json", consist_output)

        summary_sheet = parse_sheet(template, template_paths["Summary"], template_strings, template_formats)
        summary_headers = {
            column: str(cell["cached_value"] or "")
            for column, cell in summary_sheet["rows"][0][1].items() if column <= 8
        }
        summary_rows = []
        for row_number, cells in summary_sheet["rows"][1:]:
            platform = str((cells.get(1) or {}).get("cached_value") or "").strip()
            if not platform:
                continue
            summary_rows.append({
                "position": len(summary_rows) + 1,
                "row": row_number,
                "platform": platform,
                "cells": [
                    cell_payload(cells.get(column), summary_headers.get(column, ""))
                    for column in range(1, 9)
                ],
            })
        ut_values = []
        first_ut_by_platform = {}
        for row in summary_rows:
            raw = row["cells"][7]["cached_value"]
            try:
                value = int(float(raw))
                ut_values.append(value)
                first_ut_by_platform.setdefault(row["platform"], value)
            except (TypeError, ValueError):
                pass
        summary_output = {
            "sheet": "Summary",
            "row_count": len(summary_rows),
            "unique_platforms": len({row["platform"] for row in summary_rows}),
            "ut_numeric_rows": len(ut_values),
            "ut_total_raw_rows": sum(ut_values),
            "ut_total_unique_platforms": sum(first_ut_by_platform.values()),
            "merged_cells": summary_sheet["merged_cells"],
            "hidden_columns": summary_sheet["hidden_columns"],
            "hidden_rows": [
                row for row, meta in summary_sheet["row_meta"].items() if meta["hidden"]
            ],
            "rows": summary_rows,
        }
        write_json(output / "summary-output.json", summary_output)

        positions = {}
        for source, details in sources.items():
            for item in details["records"]:
                positions.setdefault(item["key"], {})[source] = item["row"] - 1
        for row in consist_rows:
            positions.setdefault(row["vin"], {})["consist"] = row["position"]
        vin_order = [
            {"vin": vin, **position}
            for vin, position in sorted(
                positions.items(), key=lambda pair: pair[1].get("consist", 10**9)
            )
        ]
        write_json(output / "vin-order.json", {
            "rule_evidence": "ODBC ORDER BY fdTrack; exact tie order is cached golden evidence",
            "same_vin_set_all_sources": all(
                set(sources["vehicle_load_report"]["index"]) == set(sources[key]["index"])
                for key in ("shippers", "cnacs")
            ),
            "rows": vin_order,
        })

        cnacs_headers = {
            column: str(cell["cached_value"] or "")
            for column, cell in sources["cnacs"]["sheet"]["rows"][0][1].items()
        }
        duplicate_cases = []
        for vin, occurrences in sorted(sources["cnacs"]["index"].items()):
            if len(occurrences) < 2:
                continue
            columns = sorted(set().union(*(set(cells) for _, cells in occurrences)))
            equal, different = [], []
            for column in columns:
                values = [
                    (cells.get(column) or {}).get("cached_value") for _, cells in occurrences
                ]
                (equal if len(set(map(str, values))) == 1 else different).append(
                    cnacs_headers.get(column, f"column_{column}")
                )
            duplicate_cases.append({
                "vin": vin,
                "repetitions": len(occurrences),
                "rows": [row for row, _ in occurrences],
                "equal_columns": equal,
                "different_columns": different,
                "classification": "duplicado idéntico" if not different else "duplicado con diferencias",
                "consist_occurrences": sum(1 for row in consist_rows if row["vin"] == vin),
                "summary_direct_vin_dependency": False,
                "resolution_evidence": "VLOOKUP de Consist toma la primera coincidencia CNACS",
            })
        write_json(output / "duplicate-analysis.json", {
            "case_count": len(duplicate_cases),
            "duplicate_occurrences": sum(case["repetitions"] - 1 for case in duplicate_cases),
            "cases": duplicate_cases,
        })

        master_route = parse_sheet(
            master, master_paths["route_codes"], master_strings, master_formats
        )
        _, route_index = rows_by_key(master_route, 1)
        route_usage: dict[str, dict] = {}
        catalog_exact = 0
        for row in consist_rows:
            values = {cell["header"]: cell["cached_value"] for cell in row["cells"]}
            route = normalized(values.get("Route Code"))
            candidates = route_index.get(route, [])
            first_row, first_cells = candidates[0] if candidates else (None, {})
            current_market = (first_cells.get(3) or {}).get("cached_value")
            current_destination = (first_cells.get(4) or {}).get("cached_value")
            matches = (
                normalized(current_market) == normalized(values.get("Bloque"))
                and normalized(current_destination) == normalized(values.get("Cruce"))
            )
            catalog_exact += int(matches)
            item = route_usage.setdefault(route, {
                "route_code": route,
                "uses": 0,
                "master_candidate_rows": [candidate[0] for candidate in candidates],
                "vlookup_first_row": first_row,
                "current_column_c_header": "Market",
                "current_column_c_value": current_market,
                "consist_header_e": "Bloque",
                "consist_cached_e": values.get("Bloque"),
                "current_column_d_header": "Shipping Destination",
                "current_column_d_value": current_destination,
                "consist_header_j": "Cruce",
                "consist_cached_j": values.get("Cruce"),
                "matches_physical_c_d": matches,
            })
            item["uses"] += 1
        catalog_resolution = {
            "consist_rows": len(consist_rows),
            "rows_matching_current_master_physical_c_d": catalog_exact,
            "conclusion": (
                "La plantilla consulta físicamente route_codes A:C/A:D. "
                "Los encabezados Bloque/Cruce de Consist son etiquetas históricas; "
                "los valores almacenados corresponden a Market/Shipping Destination."
            ),
            "certainty": "alta",
            "routes": list(route_usage.values()),
        }
        write_json(output / "catalog-resolution.json", catalog_resolution)

        consistency = {
            "vehicle_load_unique": len(sources["vehicle_load_report"]["index"]),
            "shippers_unique": len(sources["shippers"]["index"]),
            "cnacs_records": len(sources["cnacs"]["records"]),
            "cnacs_unique": len(sources["cnacs"]["index"]),
            "cnacs_duplicate_cases": len(duplicate_cases),
            "consist_rows": len(consist_rows),
            "consist_unique": len({row["vin"] for row in consist_rows}),
            "consist_vins_subset_of_inputs": set(row["vin"] for row in consist_rows).issubset(
                sources["vehicle_load_report"]["index"]
            ),
            "summary_rows": len(summary_rows),
            "summary_unique_platforms": len(first_ut_by_platform),
            "summary_ut_total_raw_rows": sum(ut_values),
            "summary_ut_total_unique_platforms": sum(first_ut_by_platform.values()),
            "summary_difference_against_consist": (
                len(consist_rows) - sum(first_ut_by_platform.values())
            ),
        }
        write_json(output / "consistency.json", consistency)
        return consistency


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("template", type=Path)
    parser.add_argument("master", type=Path)
    parser.add_argument("output", type=Path)
    args = parser.parse_args()
    consistency = build(args.template.resolve(), args.master.resolve(), args.output.resolve())
    print(json.dumps(consistency, ensure_ascii=False, indent=2))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
