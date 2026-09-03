<?php

return [

    /*
     * Path to the Tesseract OCR executable.
     */
    'tesseract_path' => env('TESSERACT_PATH', 'C:\\Program Files\\Tesseract-OCR\\tesseract.exe'),

    /*
     * Folder that CONTAINS the tessdata/ directory (i.e. tessdata_prefix/tessdata/urd.traineddata).
     */
    'tessdata_prefix' => env('TESSDATA_PREFIX', base_path('_tess')),

    /*
     * OCR language(s). Urdu requires urd.traineddata in the tessdata folder above.
     */
    'languages' => env('TESSERACT_LANGS', 'urd+eng'),

    /*
     * Page segmentation mode passed to Tesseract.
     */
    'psm' => env('TESSERACT_PSM', 6),

];
