import re

path = r'C:\SAKTI\sakti-backend\resources\views\penjaga\scan.blade.php'
data = open(path, 'rb').read()
text = data.decode('utf-8')

# â€" is double-encoded em-dash (U+2014)
# Fix: replace with &mdash; (HTML entity, safe in HTML context)
before = text.count('â€"')
text = text.replace('â€"', '&mdash;')
after = text.count('â€"')
print(f'Replaced {before - after} instances of â€" → &mdash;')

# Also fix â€™ (right single quote, double-encoded)
before2 = text.count("â€™")
text = text.replace("â€™", "&#39;")
after2 = text.count("â€™")
print(f'Replaced {before2 - after2} instances of â€™ → &#39;')

open(path, 'wb').write(text.encode('utf-8'))
print('Done. Remaining mojibake:')

# Verify
text2 = open(path, 'rb').read().decode('utf-8', errors='replace')
leftover = len(re.findall(r'â€', text2))
print(f'  â€ remaining: {leftover}')
