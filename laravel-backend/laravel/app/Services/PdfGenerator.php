<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class PdfGenerator
{
    public function generate($view, $data = [])
    {
        return Pdf::loadView($view, $data)->download('document.pdf');
    }
}
