#!/usr/bin/env python3
"""Inspección estática y de solo lectura de libros OpenXML de Consist Rail."""

from __future__ import annotations

import argparse
import hashlib
import json
import re
import zipfile
from collections import Counter
from pathlib import Path
from xml.etree import ElementTree as ET

NS = {
    "m": "http://schemas.openxmlformats.org/spreadsheetml/2006/main",
    "r": "http://schemas.openxmlformats.org/officeDocument/2006/relationships",
    "p": "http://schemas.openxmlformats.org/package/2006/relationships",
}


def sha256(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as stream:
        for block in iter(lambda: stream.read(1024 * 1024), b""):
            digest.update(block)
    return digest.hexdigest()


def xml(zip_file: zipfile.ZipFile, name: str) -> ET.Element | None:
    try:
        return ET.fromstring(zip_file.read(name))
    except KeyError:
        return None


def rel_target(base: str, target: str) -> str:
    parts = base.split("/")[:-1]
    for part in target.replace("\\", "/").split("/"):
        if part == "..":
            parts.pop()
        elif part not in ("", "."):
            parts.append(part)
    return "/".join(parts)


def shared_strings(zip_file: zipfile.ZipFile) -> list[str]:
    root = xml(zip_file, "xl/sharedStrings.xml")
    if root is None:
        return []
    return [
        "".join(node.text or "" for node in item.iterfind(".//m:t", NS))
        for item in root.findall("m:si", NS)
    ]


def cell_value(cell: ET.Element, strings: list[str]) -> str | None:
    kind = cell.get("t")
    if kind == "inlineStr":
        return "".join(node.text or "" for node in cell.iterfind(".//m:t", NS))
    value = cell.findtext("m:v", default=None, namespaces=NS)
    if value is None:
        return None
    if kind == "s":
        try:
            return strings[int(value)]
        except (ValueError, IndexError):
            return None
    return value


def col_number(reference: str) -> int:
    letters = re.match(r"[A-Z]+", reference.upper())
    number = 0
    for char in letters.group(0) if letters else "":
        number = number * 26 + ord(char) - 64
    return number


def normalize_formula(formula: str) -> str:
    return re.sub(r"(?<![A-Z0-9_])\$?[A-Z]{1,3}\$?\d+", "{CELL}", formula.upper())


def inspect(path: Path) -> dict:
    result: dict = {
        "file": path.name,
        "size": path.stat().st_size,
        "modified": path.stat().st_mtime,
        "sha256": sha256(path),
    }
    with zipfile.ZipFile(path) as book:
        names = set(book.namelist())
        result["zip_valid"] = book.testzip() is None
        result["parts"] = {
            "vba": "xl/vbaProject.bin" in names,
            "connections": sorted(n for n in names if "connection" in n.lower()),
            "queries": sorted(n for n in names if "quer" in n.lower()),
            "external_links": sorted(n for n in names if n.startswith("xl/externalLinks/")),
            "pivot_tables": sorted(n for n in names if n.startswith("xl/pivotTables/")),
            "pivot_caches": sorted(n for n in names if n.startswith("xl/pivotCache/")),
            "tables": sorted(n for n in names if n.startswith("xl/tables/") and n.endswith(".xml")),
            "controls": sorted(
                n for n in names
                if n.startswith(("xl/activeX/", "xl/ctrlProps/", "xl/drawings/"))
            ),
        }

        strings = shared_strings(book)
        workbook = xml(book, "xl/workbook.xml")
        rels = xml(book, "xl/_rels/workbook.xml.rels")
        relationships = {}
        if rels is not None:
            for rel in rels.findall("p:Relationship", NS):
                relationships[rel.get("Id")] = rel.get("Target")

        result["calc"] = {}
        result["defined_names"] = []
        result["sheets"] = []
        if workbook is not None:
            calc = workbook.find("m:calcPr", NS)
            if calc is not None:
                result["calc"] = dict(calc.attrib)
            names_node = workbook.find("m:definedNames", NS)
            if names_node is not None:
                for item in names_node:
                    result["defined_names"].append({
                        "name": item.get("name"),
                        "local_sheet_id": item.get("localSheetId"),
                        "hidden": item.get("hidden"),
                        "refers_to": item.text,
                    })

            for position, sheet in enumerate(workbook.findall("m:sheets/m:sheet", NS), 1):
                rid = sheet.get(f"{{{NS['r']}}}id")
                target = relationships.get(rid, "")
                sheet_path = rel_target("xl/workbook.xml", target)
                root = xml(book, sheet_path)
                info = {
                    "position": position,
                    "name": sheet.get("name"),
                    "state": sheet.get("state", "visible"),
                    "path": sheet_path,
                    "dimension": None,
                    "rows_with_values": 0,
                    "nonempty_cells": 0,
                    "formula_count": 0,
                    "formula_patterns": [],
                    "candidate_headers": [],
                    "merged_ranges": [],
                }
                if root is not None:
                    dimension = root.find("m:dimension", NS)
                    info["dimension"] = dimension.get("ref") if dimension is not None else None
                    patterns: Counter[str] = Counter()
                    candidate_headers = []
                    for row in root.findall("m:sheetData/m:row", NS):
                        populated = []
                        for cell in row.findall("m:c", NS):
                            value = cell_value(cell, strings)
                            formula = cell.findtext("m:f", default=None, namespaces=NS)
                            if value not in (None, "") or formula:
                                info["nonempty_cells"] += 1
                                populated.append((col_number(cell.get("r", "")), value))
                            if formula:
                                info["formula_count"] += 1
                                patterns[normalize_formula(formula)] += 1
                        if populated:
                            info["rows_with_values"] += 1
                            textual = [v for _, v in sorted(populated) if v and not v.replace(".", "", 1).isdigit()]
                            if len(textual) >= 2 and len(candidate_headers) < 12:
                                candidate_headers.append({
                                    "row": int(row.get("r", "0")),
                                    "values": textual[:40],
                                })
                    info["formula_patterns"] = [
                        {"pattern": pattern, "count": count}
                        for pattern, count in patterns.most_common(30)
                    ]
                    info["candidate_headers"] = candidate_headers
                    merges = root.find("m:mergeCells", NS)
                    if merges is not None:
                        info["merged_ranges"] = [item.get("ref") for item in merges][:100]
                result["sheets"].append(info)

        result["tables"] = []
        for table_path in result["parts"]["tables"]:
            root = xml(book, table_path)
            if root is not None:
                result["tables"].append({
                    "path": table_path,
                    "name": root.get("name"),
                    "display_name": root.get("displayName"),
                    "ref": root.get("ref"),
                    "columns": [
                        col.get("name") for col in root.findall("m:tableColumns/m:tableColumn", NS)
                    ],
                })

        result["connections"] = []
        root = xml(book, "xl/connections.xml")
        if root is not None:
            for item in root:
                result["connections"].append({
                    key: value for key, value in item.attrib.items()
                    if key.lower() not in {"password"}
                })

        if "xl/vbaProject.bin" in names:
            data = book.read("xl/vbaProject.bin")
            ascii_strings = re.findall(rb"[\x20-\x7e]{5,}", data)
            decoded = [item.decode("latin-1", errors="replace") for item in ascii_strings]
            markers = ("Sub ", "Function ", "Workbook_", "Worksheet_", "Query", "Refresh", "Consist")
            result["vba_static_strings"] = sorted({
                item[:300] for item in decoded if any(marker.lower() in item.lower() for marker in markers)
            })[:300]
        else:
            result["vba_static_strings"] = []
    return result


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("workbooks", nargs="+", type=Path)
    parser.add_argument("--output", type=Path)
    args = parser.parse_args()
    payload = [inspect(path.resolve()) for path in args.workbooks]
    rendered = json.dumps(payload, ensure_ascii=False, indent=2)
    if args.output:
        args.output.write_text(rendered + "\n", encoding="utf-8")
    else:
        print(rendered)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
