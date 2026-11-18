"""Read-only checks of disposable files produced by report-exports.php."""
from pathlib import Path
from zipfile import ZipFile
from xml.etree import ElementTree as ET
from openpyxl import load_workbook
from pypdf import PdfReader

root = Path(__file__).resolve().parent.parent
path = root / 'artifacts/informe-exportacion-prueba.xlsx'
book = load_workbook(path, data_only=False)
assert book.sheetnames == ['Resumen', 'Detalle']
sheet = book['Detalle']
assert sheet.max_row == 62 and sheet['A62'].value == '001-001-0000061'
assert sheet['A2'].data_type == 's' and sheet['C2'].data_type == 'n'
assert sheet['B2'].value == '=HYPERLINK("riesgo")' and sheet['B2'].data_type == 's'
assert sheet['B3'].value == '<f>SUM(A1)</f>' and sheet['B3'].data_type == 's'
assert sheet.freeze_panes == 'A2' and sheet.auto_filter.ref == 'A1:C62'
assert sheet['A1'].font.name == 'Inter'
assert sheet['A1'].fill.fgColor.rgb[-6:] == '063E53'
with ZipFile(path) as archive:
    for name in archive.namelist():
        if name.endswith('.xml'):
            ET.fromstring(archive.read(name))
pdf = PdfReader(root / 'artifacts/informe-exportacion-prueba.pdf')
assert len(pdf.pages) > 1
text = '\n'.join(p.extract_text() for p in pdf.pages)
assert '001-001-0000061' in text and 'Ñandutí' in text and '1.122.000' in text
for page in pdf.pages:
    assert 'FACTURA' in page.extract_text(), 'Encabezado de tabla repetido'
    assert 'Página' in page.extract_text(), 'Número de página'
    assert any('Inter' in str(f.get_object().get('/BaseFont')) for f in page['/Resources']['/Font'].values())
print('Excel: tipos, texto seguro, filtros, estilo y 61 resultados verificados.')
print(f'PDF: {len(pdf.pages)} páginas, fuente Inter, encabezados y contenido completo verificados.')
