"""Package the full plugin with public-readable files, independent of workspace modes."""
from pathlib import Path
import hashlib
import re
import stat
import zipfile

root = Path(__file__).resolve().parents[1]
source = root / 'plugin' / 'daneshgaran-school'
version = re.search(r"define\('DGS_VERSION','([^']+)'\)", (source / 'daneshgaran-school.php').read_text()).group(1)
archive = root / 'releases' / f'daneshgaran-school-{version}.zip'
with zipfile.ZipFile(archive, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as out:
    for path in [source, *sorted(source.rglob('*'))]:
        relative = path.relative_to(source.parent).as_posix()
        if path.is_dir():
            entry = zipfile.ZipInfo(relative + '/')
            entry.create_system = 3
            entry.external_attr = ((stat.S_IFDIR | 0o755) << 16) | 0x10
            out.writestr(entry, b'')
        elif path.is_file():
            entry = zipfile.ZipInfo(relative)
            entry.create_system = 3
            entry.external_attr = (stat.S_IFREG | 0o644) << 16
            out.writestr(entry, path.read_bytes(), compress_type=zipfile.ZIP_DEFLATED, compresslevel=9)
with zipfile.ZipFile(archive) as out:
    assert out.testzip() is None
    for entry in out.infolist():
        assert stat.S_IMODE(entry.external_attr >> 16) == (0o755 if entry.is_dir() else 0o644), entry.filename
    assert out.read('daneshgaran-school/assets/login-reference.jpg') == (source / 'assets' / 'login-reference.jpg').read_bytes()
sums = root / 'releases' / 'SHA256SUMS.txt'
lines = [line for line in sums.read_text().splitlines() if not line.endswith('  ' + archive.name)]
lines.append(hashlib.sha256(archive.read_bytes()).hexdigest() + '  ' + archive.name)
sums.write_text('\n'.join(lines) + '\n')
print(f'{archive.name}: {archive.stat().st_size} bytes; every file 0644, every directory 0755')
