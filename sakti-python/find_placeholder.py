import re
with open(r'C:\SAKTI\sakti-backend\resources\views\penjaga\scan.blade.php', 'rb') as f:
    data = f.read()
idx = data.find(b'<td><span class="text-muted">')
print('Found at:', idx)
if idx >= 0:
    print('Bytes hex:', data[idx:idx+60].hex())
    print('Repr:', repr(data[idx:idx+60]))
