<?php

namespace App\Shared\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PdfGeneratorService
{
    /**
     * Service fototra amin'ny famoronana sy fitehirizana PDFs (Contrats, Quittances, Factures)
     */
    public function savePdf(string $pdfContent, string $subFolder = 'pdfs'): string
    {
        $filename = $subFolder . '/' . Str::uuid() . '.pdf';
        Storage::disk('public')->put($filename, $pdfContent);
        return $filename;
    }
}
