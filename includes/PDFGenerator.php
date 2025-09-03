<?php
namespace WCIPB;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class PDFGenerator {
    public function is_available() {
        return class_exists('\\Dompdf\\Dompdf');
    }

    public function stream($html, $filename='inventory.pdf') {
        if ( ! $this->is_available() ) return false;

        // Lazy-load composer autoloader if present inside plugin folder
        $autoload = WCIPB_PATH . 'vendor/autoload.php';
        if ( file_exists($autoload) ) {
            require_once $autoload;
        }

        $dompdf = new \Dompdf\Dompdf([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultPaperSize' => 'letter',
            'defaultPaperOrientation' => 'portrait'
        ]);

        $css = '<style>
            body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; }
            table { width:100%; border-collapse: collapse; }
            th, td { border:1px solid #999; padding:6px; vertical-align:top; }
            th { background:#eee; }
            .nowrap { white-space: nowrap; }
        </style>';

        $dompdf->loadHtml($css . $html, 'UTF-8');
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();
        $dompdf->stream($filename, ['Attachment'=>true]);
        return true;
    }
}
