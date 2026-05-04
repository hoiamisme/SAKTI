old1 = b"${(pesan||'').substring(0,60) || '\xef\xbf\xbd'}"
new1 = b"${(pesan||'').substring(0,60) || '&mdash;'}"

path = r'C:\SAKTI\sakti-backend\resources\views\penjaga\scan.blade.php'
with open(path, 'rb') as f:
    data = f.read()

count = data.count(old1)
print(f'keterangan placeholder count: {count}')
if count == 1:
    data = data.replace(old1, new1, 1)
    with open(path, 'wb') as f:
        f.write(data)
    print('Done')
