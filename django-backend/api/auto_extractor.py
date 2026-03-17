import os
import shutil

# Remove directories
for d in ['ia_models', 'assurance', 'gestion_rh']:
    d_path = os.path.join('/home/vicmejj/unified_platform/django-backend', d)
    if os.path.exists(d_path):
        shutil.rmtree(d_path)

# Extract PDF
try:
    import PyPDF2
    reader = PyPDF2.PdfReader('/home/vicmejj/unified_platform/Documentation_Technique.pdf')
    with open('/home/vicmejj/unified_platform/django-backend/pdf_content.txt', 'w') as f:
        for page in reader.pages:
            f.write(page.extract_text() + "\n")
except Exception as e:
    with open('/home/vicmejj/unified_platform/django-backend/pdf_content.txt', 'w') as f:
        f.write(f"Error: {str(e)}")
