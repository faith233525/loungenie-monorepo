import os
import docx
from PyPDF2 import PdfReader

def extract_docx_text(filepath):
    doc = docx.Document(filepath)
    return '\n'.join([para.text for para in doc.paragraphs if para.text.strip()])

def extract_pdf_text(filepath):
    text = []
    with open(filepath, 'rb') as f:
        reader = PdfReader(f)
        for page in reader.pages:
            page_text = page.extract_text()
            if page_text:
                text.append(page_text)
    return '\n'.join(text)

def main():
    folder = r'C:\Users\pools\OneDrive - Pool Safe Inc\Desktop\Introduction to Loungenie'
    files = os.listdir(folder)
    output = []
    for file in files:
        path = os.path.join(folder, file)
        if file.lower().endswith('.docx'):
            try:
                text = extract_docx_text(path)
                output.append(f'--- {file} ---\n{text}')
            except Exception as e:
                output.append(f'--- {file} ---\n[Error extracting DOCX: {e}]')
        elif file.lower().endswith('.pdf'):
            try:
                text = extract_pdf_text(path)
                output.append(f'--- {file} ---\n{text}')
            except Exception as e:
                output.append(f'--- {file} ---\n[Error extracting PDF: {e}]')
    with open('all_testimonials_content.txt', 'w', encoding='utf-8') as f:
        f.write('\n\n'.join(output))
    print('Extraction complete. See all_testimonials_content.txt')

if __name__ == '__main__':
    main()
