#!/usr/bin/env python3
"""Verify an offline wheel directory for Win7 / CPython 3.8.

`pip download --platform win_amd64` still evaluates some environment markers
against the build host.  A macOS build can therefore omit dependencies guarded
by `platform_system == "Windows"` even though every download command succeeds.
This checker evaluates wheel metadata with the target environment instead.
"""

from __future__ import annotations

import email
import pathlib
import sys
import zipfile

try:
    from packaging.markers import default_environment
    from packaging.requirements import Requirement
    from packaging.specifiers import SpecifierSet
    from packaging.utils import canonicalize_name, parse_wheel_filename
    from packaging.version import Version
except ImportError:  # pip vendors packaging even on minimal build machines
    from pip._vendor.packaging.markers import default_environment
    from pip._vendor.packaging.requirements import Requirement
    from pip._vendor.packaging.specifiers import SpecifierSet
    from pip._vendor.packaging.utils import canonicalize_name, parse_wheel_filename
    from pip._vendor.packaging.version import Version


TARGET_PYTHON = Version("3.8.10")
TARGET_ENV = default_environment()
TARGET_ENV.update(
    {
        "implementation_name": "cpython",
        "implementation_version": "3.8.10",
        "os_name": "nt",
        "platform_machine": "AMD64",
        "platform_python_implementation": "CPython",
        "platform_release": "7",
        "platform_system": "Windows",
        "platform_version": "6.1.7601",
        "python_full_version": "3.8.10",
        "python_version": "3.8",
        "sys_platform": "win32",
        "extra": "",
    }
)


def fail(messages: list[str]) -> int:
    print("OCR offline wheel closure FAILED:", file=sys.stderr)
    for message in sorted(set(messages)):
        print(f"  - {message}", file=sys.stderr)
    return 1


def read_lock(lock_path: pathlib.Path) -> list[Requirement]:
    requirements: list[Requirement] = []
    for raw_line in lock_path.read_text(encoding="utf-8").splitlines():
        line = raw_line.strip()
        if not line or line.startswith("#"):
            continue
        requirements.append(Requirement(line))
    return requirements


def read_metadata(wheel_path: pathlib.Path):
    with zipfile.ZipFile(str(wheel_path)) as archive:
        metadata_names = [
            name for name in archive.namelist() if name.endswith(".dist-info/METADATA")
        ]
        if len(metadata_names) != 1:
            raise ValueError(f"expected one METADATA file, found {len(metadata_names)}")
        return email.message_from_bytes(archive.read(metadata_names[0]))


def main() -> int:
    if len(sys.argv) != 3:
        print("usage: verify-ocr-wheel-closure.py LOCK_FILE WHEEL_DIR", file=sys.stderr)
        return 2

    lock_path = pathlib.Path(sys.argv[1])
    wheel_dir = pathlib.Path(sys.argv[2])
    errors: list[str] = []

    if not lock_path.is_file():
        return fail([f"lock file not found: {lock_path}"])
    if not wheel_dir.is_dir():
        return fail([f"wheel directory not found: {wheel_dir}"])

    available: dict[str, list[tuple[Version, pathlib.Path]]] = {}
    metadata_by_wheel = {}
    for wheel_path in sorted(wheel_dir.glob("*.whl")):
        try:
            name, version, _build, _tags = parse_wheel_filename(wheel_path.name)
            metadata = read_metadata(wheel_path)
        except Exception as exc:  # malformed wheel is a release-blocking artifact
            errors.append(f"{wheel_path.name}: cannot read wheel metadata: {exc}")
            continue
        key = canonicalize_name(name)
        available.setdefault(key, []).append((version, wheel_path))
        metadata_by_wheel[wheel_path] = metadata

    def matching_wheels(requirement: Requirement):
        candidates = available.get(canonicalize_name(requirement.name), [])
        return [item for item in candidates if requirement.specifier.contains(item[0], prereleases=True)]

    try:
        locked = read_lock(lock_path)
    except Exception as exc:
        return fail([f"cannot parse {lock_path.name}: {exc}"])

    for requirement in locked:
        if requirement.marker and not requirement.marker.evaluate(TARGET_ENV):
            continue
        if not matching_wheels(requirement):
            errors.append(f"locked requirement has no matching wheel: {requirement}")

    for wheel_path, metadata in metadata_by_wheel.items():
        requires_python = metadata.get("Requires-Python")
        if requires_python and not SpecifierSet(requires_python).contains(TARGET_PYTHON):
            errors.append(
                f"{wheel_path.name} requires Python {requires_python}, target is {TARGET_PYTHON}"
            )
        for raw_requirement in metadata.get_all("Requires-Dist", []):
            try:
                requirement = Requirement(raw_requirement)
            except Exception as exc:
                errors.append(f"{wheel_path.name}: invalid Requires-Dist {raw_requirement!r}: {exc}")
                continue
            if requirement.marker and not requirement.marker.evaluate(TARGET_ENV):
                continue
            if not matching_wheels(requirement):
                errors.append(f"{wheel_path.name} needs {requirement}, but no matching wheel exists")

    source_archives = sorted(wheel_dir.glob("*.tar.gz"))
    if source_archives:
        errors.append(
            "source archives cannot be proven offline/Win7 compatible: "
            + ", ".join(path.name for path in source_archives)
        )

    if errors:
        return fail(errors)

    print(
        f"OCR offline wheel closure OK: {len(available)} distributions, "
        f"target Windows 7 / CPython {TARGET_PYTHON}"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
