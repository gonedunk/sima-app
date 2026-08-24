<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class PdfService
{
    /**
     * Mengompilasi HTML menjadi dokumen PDF menggunakan Dompdf
     * 
     * @param string $html         Struktur HTML dari view
     * @param string $filename     Nama file cetak ketika diunduh
     * @param string $paper        Ukuran kertas (A4, F4, Letter, dll)
     * @param string $orientation  Orientasi halaman ('portrait' atau 'landscape')
     */
    public static function cetakLaporan($html, $filename, $paper = 'A4', $orientation = 'portrait')
    {
        // Konversi parameter orientasi mPDF ('P' atau 'L') ke format text panjang Dompdf
        $layoutOrientation = (strtolower($orientation) === 'p' || $orientation === 'portrait') ? 'portrait' : 'landscape';

        // Load string HTML dan atur konfigurasi kertas
        $pdf = Pdf::loadHTML($html)
                  ->setPaper($paper, $layoutOrientation)
                  ->setWarnings(false);

        // Alirkan langsung ke browser (Inline Stream)
        return $pdf->stream($filename . '.pdf');
    }
}