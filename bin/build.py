#!/usr/bin/env python3
"""Build dist/stamp-show-companion-<version>.zip, ready to upload in WP admin (Plugins > Add New > Upload)."""
import re
import sys
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SLUG = 'stamp-show-companion'
INCLUDE = ['stamp-show-companion.php', 'uninstall.php', 'README.md', 'includes', 'assets']

header = (ROOT / 'stamp-show-companion.php').read_text(encoding='utf-8')
version = re.search(r'^\s*\*\s*Version:\s*(\S+)', header, re.M).group(1)
const = re.search(r"define\('SSC_VERSION',\s*'([^']+)'", header).group(1)
if version != const:
    sys.exit(f'Version mismatch: header {version} vs SSC_VERSION {const}')

out = ROOT / 'dist' / f'{SLUG}-{version}.zip'
out.parent.mkdir(exist_ok=True)
out.unlink(missing_ok=True)

files = []
for name in INCLUDE:
    p = ROOT / name
    files += [p] if p.is_file() else sorted(f for f in p.rglob('*') if f.is_file())

with zipfile.ZipFile(out, 'w', zipfile.ZIP_DEFLATED) as z:
    for f in files:
        # WordPress needs a single top-level folder named after the plugin; forward slashes always.
        z.write(f, f'{SLUG}/{f.relative_to(ROOT).as_posix()}')

print(f'{out} ({len(files)} files)')
