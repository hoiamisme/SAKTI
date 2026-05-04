path = r'C:\SAKTI\sakti-backend\resources\views\penjaga\scan.blade.php'
data = open(path, 'rb').read()

# Get bytes at known position 5606 (text position)
# Text was decoded with errors='replace', so text position != byte position exactly
# Use re to find the bytes around the known pattern

# The pattern we're looking for in the raw bytes is the mojibake sequence
# Let's find what bytes exist between ">â€"</div>"
# Look for >....< pattern near "resultName"

import re

# Find 'resultName">' in bytes
marker = b'id="resultName">'
pos = data.find(marker)
print(f'resultName marker at byte {pos}')
if pos >= 0:
    snippet = data[pos:pos+30]
    print(f'Bytes: {snippet.hex()}')
    print(f'As string: {repr(snippet)}')
    
    # The mojibake bytes come right after the >
    start = pos + len(marker)
    end = data.find(b'</div>', start)
    mojibake_bytes = data[start:end]
    print(f'Mojibake bytes: {mojibake_bytes.hex()}')
    print(f'Mojibake repr: {repr(mojibake_bytes)}')
