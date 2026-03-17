import zlib
import re

pdf_path = '/home/vicmejj/unified_platform/Documentation_Technique.pdf'
out_path = '/home/vicmejj/unified_platform/pdf_content.txt'

try:
    with open(pdf_path, 'rb') as f:
        pdf_data = f.read()
        
    extracted = []
    
    # Simple regex to grab zlib decompressed streams
    for match in re.finditer(b'stream[\r\n\s]+(.*?)[\r\n\s]+endstream', pdf_data, re.DOTALL):
        try:
            decoded = zlib.decompress(match.group(1))
            for txt in re.finditer(b'\((.*?)\)', decoded):
                text = txt.group(1).decode('latin-1', 'ignore')
                if len(text.strip()) > 2:
                    extracted.append(text)
        except Exception:
            continue
            
    with open(out_path, 'w', encoding='utf-8') as f:
        f.write("\n".join(extracted))
        
    print(f"Success! Extracted {len(extracted)} text blocks to pdf_content.txt")
    
except Exception as e:
    print(f"Error: {e}")
