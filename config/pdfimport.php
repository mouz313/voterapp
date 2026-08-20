<?php

return [

    /*
     * Path to the poppler `pdftotext` binary (used for digital/text PDFs).
     * It is already available on this machine via Git's mingw bin.
     */
    'pdftotext_path' => env('PDFTOTEXT_PATH', 'pdftotext'),

    /*
     * Path to the Tesseract OCR binary (used only for scanned PDFs).
     * Requires a one-time install + Urdu `urd.traineddata` in tessdata.
     */
    'tesseract_path' => env('TESSERACT_PATH', 'tesseract'),

    /*
     * Path to the Python interpreter (used to rasterize scanned PDFs to images).
     */
    'python_path' => env('PYTHON_PATH', 'python'),

    /*
     * Enable OCR for scanned PDFs. Leave false until Tesseract + a PDF
     * rasterizer (e.g. `pip install pymupdf`) are installed on the server.
     */
    'ocr_enabled' => env('PDF_OCR_ENABLED', false),

    /*
     * Temp directory (relative to storage_path) for uploaded PDFs and
     * intermediate OCR images / parsed-row JSON.
     */
    'tmp_dir' => 'pdfimport',
];
