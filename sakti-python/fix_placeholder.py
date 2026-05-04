old_bytes = b'<td><span class="text-muted">\xef\xbf\xbd</span></td>'
new_str = """<td>${snapshotUrl
                ? `<img src="${snapshotUrl}" alt="CCTV" width="36" height="36" class="rounded border object-fit-cover" style="cursor:pointer;" data-bs-toggle="modal" data-bs-target="#imgModal" data-src="${snapshotUrl}" data-info="${kadet?.nama_lengkap||uid}">`
                : '<span class="text-muted">&mdash;</span>'}</td>"""
new_bytes = new_str.encode('utf-8')

path = r'C:\SAKTI\sakti-backend\resources\views\penjaga\scan.blade.php'
with open(path, 'rb') as f:
    data = f.read()

count = data.count(old_bytes)
print(f'Occurrences: {count}')
if count == 1:
    data = data.replace(old_bytes, new_bytes, 1)
    with open(path, 'wb') as f:
        f.write(data)
    print('Done')
else:
    print('Unexpected count, not replacing')
