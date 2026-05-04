path = r'C:\SAKTI\sakti-backend\resources\views\penjaga\scan.blade.php'
data = open(path, 'rb').read()

# Mojibake bytes = double-encoded em-dash: c3a2 e282ac e2809d
bad_bytes  = b'\xc3\xa2\xe2\x82\xac\xe2\x80\x9d'
good_bytes = b'&mdash;'

count = data.count(bad_bytes)
fixed = data.replace(bad_bytes, good_bytes)
print(f'Replaced {count} instances')
open(path, 'wb').write(fixed)
print('Done.')
