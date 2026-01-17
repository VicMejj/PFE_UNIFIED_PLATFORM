<?php

namespace App\Http\Controllers;

use App\Services\PdfGenerator;

class PdfController extends Controller
{
    protected PdfGenerator $pdf;

    public function __construct(PdfGenerator $pdf)
    {
        $this->pdf = $pdf;
    }

    public function export()
    {
        return $this->pdf->generate('pdf.report', [
            'title' => 'RH Report'
        ]);
    }
}
