import sys

data = open(r'C:\SAKTI\sakti-backend\resources\views\penjaga\scan.blade.php','rb').read()
print('FFFD count before:', data.count(b'\xef\xbf\xbd'))

fffd = b'\xef\xbf\xbd'

# Go through all occurrences and replace based on surrounding context
result = bytearray()
i = 0
replaced = 0
while i < len(data):
    if data[i:i+3] == fffd:
        # Check context to decide replacement
        ctx_before = data[max(0,i-80):i]
        ctx_after  = data[i+3:i+80]

        if b'resultNim' in ctx_before or b'kadet.prodi' in ctx_after:
            # nim · prodi separator
            result.extend(b' &#183; ')
        elif b'data-info' in ctx_before or b'waktu_akses' in ctx_after:
            # data-info separator between name and time
            result.extend(b' | ')
        elif b'text-muted' in ctx_before and b'</span>' in ctx_after:
            # no-snapshot fallback text
            result.extend(b'&mdash;')
        elif b'substring(0,60)' in ctx_before:
            # keterangan fallback
            result.extend(b'&mdash;')
        elif b'RFID Input' in ctx_before:
            # comment
            result.extend(b'--')
        elif b'Kamera aktif' in ctx_before:
            # status message
            result.extend('\u2014'.encode('utf-8'))
        else:
            # fallback: em dash
            result.extend('\u2014'.encode('utf-8'))
        replaced += 1
        i += 3
    else:
        result.append(data[i])
        i += 1

print(f'Replaced {replaced} occurrences')
data = bytes(result)
print('FFFD count after:', data.count(b'\xef\xbf\xbd'))
open(r'C:\SAKTI\sakti-backend\resources\views\penjaga\scan.blade.php','wb').write(data)
print('Written OK')
