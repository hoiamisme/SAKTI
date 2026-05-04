import re

# Fix mojibake in scan.blade.php
# 1. Replace â€" (double-encoded em-dash) with proper &mdash;
data = open(r'C:\SAKTI\sakti-backend\resources\views\penjaga\scan.blade.php', 'rb').read()

# â€" in UTF-8 bytes = c3 a2 e2 80 9c or similar?
# Actually: â = c3 a2, € = e2 82 ac, " = e2 80 9d? 
# Let me find the exact bytes
text = data.decode('utf-8', errors='replace')
mojibake_emph = 'â\u20ac\u201d'  # UTF-8 encoding of â€" chars

# Find the actual byte sequence
sample_pos = text.index('â€"') if 'â€"' in text else -1
if sample_pos >= 0:
    # Get the bytes for 'â€"' 
    sample_bytes = data[sample_pos:sample_pos+6]
    print(f'Bytes for â€": {sample_bytes.hex()}')
    
    # Build replacement: replace these bytes with &mdash; bytes
    replace_bytes = b'&mdash;'
    
    count_before = data.count(sample_bytes)
    data_new = data.replace(sample_bytes, replace_bytes)
    count_after = data_new.count(sample_bytes)
    print(f'Replaced {count_before} occurrences, {count_after} remaining')
    
    open(r'C:\SAKTI\sakti-backend\resources\views\penjaga\scan.blade.php', 'wb').write(data_new)
    print('Written OK')
else:
    print('Not found!')
