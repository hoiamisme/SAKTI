import re

data = open(r'C:\SAKTI\sakti-backend\resources\views\penjaga\scan.blade.php','rb').read()

# Find mojibake: â€" is double-encoded em-dash
# em-dash U+2014 = UTF-8: e2 80 94
# When incorrectly re-encoded: c3 a2 + c2 80 + c2 94 (each byte re-encoded as UTF-8)
# OR: c3 a2 + e2 80 + 9c etc.
# Let's just search the text
text = data.decode('utf-8', errors='replace')

# Show occurrences of â€
import re
matches = [(m.start(), text[max(0,m.start()-30):m.start()+30]) for m in re.finditer('â€', text)]
print(f'Found {len(matches)} mojibake instances')
for pos, ctx in matches[:8]:
    print(f'  pos {pos}: {repr(ctx)}')
