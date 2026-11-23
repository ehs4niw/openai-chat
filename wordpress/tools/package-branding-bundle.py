"""Full theme and plugin branding bundle for extraction into wp-content, not a WP installer ZIP."""
from pathlib import Path
import hashlib
import re
import stat
import subprocess
import sys
import zipfile

root = Path(__file__).resolve().parents[1]
subprocess.run([sys.executable, str(root / 'tools/package-school-plugin.py')], check=True)
plugin = root / 'plugin/daneshgaran-school'
theme = root / 'theme/daneshgaran'
pv = re.search(r"define\('DGS_VERSION','([^']+)'\)", (plugin / 'daneshgaran-school.php').read_text()).group(1)
tv = re.search(r'^Version:\s*(.+)$', (theme / 'style.css').read_text(), re.M).group(1).strip()

def pack_entry(out, name, data=None):
    entry = zipfile.ZipInfo(name + '/' if data is None else name)
    entry.create_system = 3
    entry.external_attr = ((stat.S_IFDIR | 0o755) << 16) | 0x10 if data is None else (stat.S_IFREG | 0o644) << 16
    out.writestr(entry, b'' if data is None else data, compress_type=zipfile.ZIP_DEFLATED, compresslevel=9)

def add_tree(out, source, prefix):
    for p in [source, *sorted(source.rglob('*'))]:
        pack_entry(out, prefix + '/' + p.relative_to(source.parent).as_posix(), None if p.is_dir() else p.read_bytes())

archive = root / 'releases' / f'daneshgaran-theme-{tv}.zip'
with zipfile.ZipFile(archive, 'w') as out:
    for p in [theme, *sorted(theme.rglob('*'))]:
        pack_entry(out, p.relative_to(theme.parent).as_posix(), None if p.is_dir() else p.read_bytes())
archives = [archive]
archive = root / 'releases' / f'daneshgaran-branding-{pv}-cpanel.zip'
with zipfile.ZipFile(archive, 'w') as out:
    for prefix, source in [('plugins', plugin), ('themes', theme)]:
        pack_entry(out, prefix)
        add_tree(out, source, prefix)
archives.append(archive)
sums = root / 'releases/SHA256SUMS.txt'
lines = sums.read_text().splitlines()
for archive in archives:
    with zipfile.ZipFile(archive) as out:
        assert out.testzip() is None
        for entry in out.infolist():
            assert stat.S_IMODE(entry.external_attr >> 16) == (0o755 if entry.is_dir() else 0o644)
    lines = [x for x in lines if not x.endswith('  ' + archive.name)]
    lines.append(hashlib.sha256(archive.read_bytes()).hexdigest() + '  ' + archive.name)
    print(archive.name, archive.stat().st_size, 'bytes; normalized 0644/0755')
sums.write_text('\n'.join(lines) + '\n')
