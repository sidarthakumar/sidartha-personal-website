#!/usr/bin/env python3
"""Offline integrity check for the two distributable WordPress components.

This intentionally never connects to staging, production, or a database.
"""
from __future__ import annotations

import hashlib
import json
from pathlib import Path, PurePosixPath
import re
import stat
import sys
import zipfile

ROOT = Path(__file__).resolve().parents[2]
DIST = ROOT / "dist"
SOURCE = {
    "sidartha": ROOT / "wp-content/themes/sidartha",
    "sidartha-portfolio": ROOT / "wp-content/plugins/sidartha-portfolio",
}
ALLOWED = {".php", ".css", ".js", ".json", ".txt", ".png"}


def fail(message: str) -> None:
    raise ValueError(message)


def source_version(root: Path) -> str:
    metadata = root / ("style.css" if root.name == "sidartha" else "sidartha-portfolio.php")
    contents = metadata.read_text(encoding="utf-8")
    match = re.search(r"^\s*\*?\s*Version:\s*([0-9]+(?:\.[0-9]+){2})\s*$", contents, re.MULTILINE)
    if not match:
        fail(f"Missing component version: {root.name}")
    return match.group(1)


def verify() -> None:
    manifest_path = DIST / "manifest.json"
    manifest = json.loads(manifest_path.read_text(encoding="utf-8"))
    expected_names = {
        f"{slug}-{source_version(folder)}.zip": (slug, folder)
        for slug, folder in SOURCE.items()
    }
    if set(manifest) != set(expected_names):
        fail("Manifest names do not match the current theme and plugin versions")

    for filename, (slug, folder) in sorted(expected_names.items()):
        zip_path = DIST / filename
        payload = zip_path.read_bytes()
        metadata = manifest[filename]
        if len(payload) != metadata.get("bytes"):
            fail(f"Package length mismatch: {filename}")
        if hashlib.sha256(payload).hexdigest() != metadata.get("sha256"):
            fail(f"Checksum mismatch: {filename}")

        expected_files = {}
        for local in folder.rglob("*"):
            if local.is_symlink():
                fail(f"Source symlink is prohibited: {slug}")
            if not local.is_file():
                continue
            relative = local.relative_to(folder)
            if local.suffix not in ALLOWED or any(part.startswith(".") for part in relative.parts):
                fail(f"Unexpected source member under {slug}: {relative}")
            expected_files[f"{slug}/{relative.as_posix()}"] = local.read_bytes()

        with zipfile.ZipFile(zip_path) as archive:
            records = archive.infolist()
            names = [entry.filename for entry in records]
            if len(names) != len(set(names)):
                fail(f"Duplicate ZIP members: {filename}")
            if set(names) != set(expected_files):
                fail(f"Source/ZIP file-set mismatch: {filename}")
            if len(names) != metadata.get("files"):
                fail(f"Manifest file count mismatch: {filename}")
            if archive.testzip() is not None:
                fail(f"ZIP CRC failure: {filename}")
            for entry in records:
                path = PurePosixPath(entry.filename)
                if (
                    path.is_absolute()
                    or ".." in path.parts
                    or not path.parts
                    or path.parts[0] != slug
                    or entry.is_dir()
                    or stat.S_ISLNK(entry.external_attr >> 16)
                    or archive.read(entry) != expected_files[entry.filename]
                ):
                    fail(f"Unsafe or inconsistent ZIP entry: {filename}")

        print(f"PASS {filename}: checksums, versions, paths and source match")


if __name__ == "__main__":
    try:
        verify()
    except (OSError, ValueError, zipfile.BadZipFile, KeyError, TypeError) as exc:
        print(f"FAIL release verification: {exc}", file=sys.stderr)
        raise SystemExit(1) from None
