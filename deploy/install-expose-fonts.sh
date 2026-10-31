#!/usr/bin/env bash
# Self-host the ORIGINAL user-provided Expose archive; no CDN and no font bundling.
set -euo pipefail
if [ "$#" -lt 1 ]; then
    echo 'Usage: bash deploy/install-expose-fonts.sh /path/to/Expose_Complete.zip' >&2
    exit 2
fi
archive="$1"
root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
python3 - "$archive" "$root/public/fonts/expose" <<'PY'
from pathlib import Path
import sys,zipfile
source, dest = Path(sys.argv[1]), Path(sys.argv[2])
if not source.is_file(): raise SystemExit(f'Font archive not found: {source}')
required=['Expose-Regular.woff2','Expose-Medium.woff2','Expose-Bold.woff2','Expose-Black.woff2']
with zipfile.ZipFile(source) as z:
    names={Path(n).name:n for n in z.namelist() if '/Fonts/WEB/fonts/' in n and n.endswith('.woff2')}
    missing=[name for name in required if name not in names]
    if missing: raise SystemExit('Missing WOFF2 fonts: '+', '.join(missing))
    # Only extract these four approved faces. Never extract paths from the ZIP.
    dest.mkdir(parents=True,exist_ok=True)
    for name in required:
        data=z.read(names[name]);
        if not data.startswith(b'wOF2'): raise SystemExit(f'Invalid font: {name}')
        target=dest/name
        target.write_bytes(data)
        target.chmod(0o644)
        print(f'Installed {target.relative_to(dest.parent.parent.parent)} ({len(data)} bytes)')
print('Expose is self-hosted at /fonts/expose/ — no external font requests required.')
PY
