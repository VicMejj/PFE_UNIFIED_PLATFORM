import os
import sys
import shutil
import PyPDF2
from django.http import HttpResponse

def temp_maintenance(request):
    base_dir = '/home/vicmejj/unified_platform/django-backend'
    
    # Delete dirs
    for d in ['ia_models', 'assurance', 'gestion_rh']:
        dp = os.path.join(base_dir, d)
        if os.path.exists(dp):
            shutil.rmtree(dp)
            
    # Read PDF
    pdf_path = '/home/vicmejj/unified_platform/Documentation_Technique.pdf'
    text = "PDF not found"
    if os.path.exists(pdf_path):
        try:
            reader = PyPDF2.PdfReader(pdf_path)
            pages = [page.extract_text() for page in reader.pages]
            text = "\n\n--- PAGE ---\n\n".join(pages)
        except Exception as e:
            text = f"Error reading PDF: {str(e)}"
            
    return HttpResponse(text, content_type="text/plain")
