import zlib
import re
from django.http import HttpResponse

def read_doc(request):
    try:
        with open('/home/vicmejj/unified_platform/Documentation_Technique.pdf', 'rb') as f:
            pdf = f.read()
            
        extracted_text = []
        
        # Find all streams and try to decompress them
        for match in re.finditer(b'stream[\r\n\s]+(.*?)[\r\n\s]+endstream', pdf, re.DOTALL):
            stream_data = match.group(1)
            try:
                decoded = zlib.decompress(stream_data)
                
                # PDF text is usually enclosed in parentheses: (Text) Tj or [ (Text) 120 (More Text) ] TJ
                for txt in re.finditer(b'\((.*?)\)', decoded):
                    # Replace basic PDF escapes and octals if necessary, but latin-1 is usually enough to see the content
                    try:
                        extracted = txt.group(1).decode('latin-1', 'ignore')
                        # Filter out very short or garbage strings if we want, but let's grab everything
                        if len(extracted) > 3 or extracted.strip():
                            extracted_text.append(extracted)
                    except:
                        pass
            except:
                continue
                
        return HttpResponse("\n".join(extracted_text), content_type="text/plain")
    except Exception as e:
        return HttpResponse(f"Error reading PDF: {str(e)}", content_type="text/plain")
