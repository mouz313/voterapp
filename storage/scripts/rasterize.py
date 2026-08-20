import sys, os

try:
    import fitz  # PyMuPDF
except Exception:
    sys.stderr.write("PyMuPDF not installed. Run: pip install pymupdf\n")
    sys.exit(2)

if len(sys.argv) < 3:
    sys.stderr.write("usage: rasterize.py <pdf> <outdir>\n")
    sys.exit(1)

pdf, outdir = sys.argv[1], sys.argv[2]
os.makedirs(outdir, exist_ok=True)

doc = fitz.open(pdf)
for i, page in enumerate(doc):
    pix = page.get_pixmap(dpi=300)
    pix.save(os.path.join(outdir, "page-%04d.png" % (i + 1)))

print("OK")
