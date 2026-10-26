from pathlib import Path
import zipfile, hashlib
source=Path('/mnt/data/Archive(20261026-064215).zip')
root=Path('/mnt/data/nextplay_logo_work')
original={}
with zipfile.ZipFile(source) as z:
 for f in z.infolist():
  if not f.is_dir() and not f.filename.startswith('__MACOSX/') and '/.DS_Store' not in f.filename and not f.filename.endswith('.DS_Store'):
   original[f.filename]=hashlib.sha256(z.read(f)).hexdigest()
changed=[]
allfiles=[]
for p in root.rglob('*'):
 if not p.is_file(): continue
 rel=p.relative_to(root).as_posix()
 if rel in ('apply_branding_patch.py','add_tests.py','package_patch.py') or rel.startswith('__MACOSX/') or rel.endswith('.DS_Store'):
  continue
 allfiles.append(rel)
 digest=hashlib.sha256(p.read_bytes()).hexdigest()
 if original.get(rel)!=digest:changed.append(rel)
print('CHANGED FILES:',len(changed))
for f in changed:print('-',f, '[new]' if f not in original else '[modified]')
for output, names in [
 ('/mnt/data/NextPlay_Logo_Branding_Updated_Files_20261026.zip', changed),
 ('/mnt/data/NextPlay_Logo_Branding_Full_Updated_20261026.zip', allfiles),
]:
 with zipfile.ZipFile(output,'w',compression=zipfile.ZIP_DEFLATED,compresslevel=7) as z:
  for rel in sorted(names):z.write(root/rel,arcname=rel)
 print(output, Path(output).stat().st_size)
assert len(changed)==11, (len(changed), changed)
