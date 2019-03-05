<?php

use Monolog\Logger;

abstract class UserPdf
{
    protected $logger;
    protected $seller;
    protected $type;

    public function __construct($logger, $type)
    {
        $this->logger = $logger;
        $this->type = $type;
    }

    public function setSeller($id)
    {
        $this->logger->debug('load seller (user id : ' . $id . ')');
        $this->seller = SellerQuery::create()->findOneById($id);
    }

    abstract public function generate();

    protected function initPdf()
    {
        $pdf = new PdfCode39('P', 'mm', 'A4');

        $pdf->AddPage();

        $pdf->SetAutoPageBreak(false);
        $pdf->SetLineWidth(0.1);
        $pdf->SetDrawColor(240, 240, 255);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->SetFont('Arial', 'B', 12);

        return $pdf;
    }

    protected function closePdf($pdf)
    {
        $filepath = $this->getPath();
        $this->logger->debug('generate pdf');
        $pdf->Output($filepath, 'F');
        return $filepath;
    }

    private function getPath()
    {
        $path = 'pdf/' . $this->seller->getPathSecret();

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $file = date("Y-m-d_H\hi\ms\s") . '_' . $this->type . '.pdf';

        $this->logger->debug('determine path: ' . $path . '/' . $file);

        return $path . '/' . $file;
    }
}
