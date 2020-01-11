<?php

class ItemListPdf extends UserPdf
{
    private $items;

    public function __construct($logger)
    {
        parent::__construct($logger, 'liste');
    }

    public function initItems()
    {
        $this->logger->debug('load all items for seller');
        $this->items = $this->seller->getItems();
    }

    public function generate()
    {
        $pdf = $this->initPdf();

        $x = 1;
        $i = 1;

        $this->addHeader($pdf);

        $fee = 0;

        foreach ($this->items as $item) {
            if ($i%2) {
                $pdf->Rect(0, 21+$i*6.5, 300, 6.5, 'F');
            }

            if ($x > 30) {
                $fee += 0.80;
            } else {
                $fee += 0.5;
            }

            $pdf->SetXY(12, 20+$i*6.5);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(40, 10, $x);

            $pdf->SetXY(20, 20+$i*6.5);
            $pdf->Cell(40, 10, $item->getId());

            $pdf->SetXY(40, 20+$i*6.5);
            $pdf->Cell(40, 10, $item->getNameForPdf());

            $pdf->SetXY(160, 20+$i*6.5);
            $pdf->Cell(40, 10, $item->getPriceForPdf(), 0, 0, 'R');
            $x++;
            $i++;

            if ($i >= 39) {
                $i = 0;
                $pdf->AddPage();
                $this->addHeader($pdf);
            }
        }

        $pdf->SetXY(20, 280);
        $pdf->Cell(40, 10, utf8_decode('Einstellgebühr alle Spiele: ' . number_format($fee, 2, ',', '.')) . ' ' . chr(128) . ' (' . min($x-1, 30) .'x0,50 ' . chr(128) . ' / ' . max($x-31, 0) .'x0,80 ' . chr(128) . ')');

        return $this->closePdf($pdf);
    }

    private function addHeader($pdf)
    {
        $pdf->SetXY(12, 10);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(40, 10, 'Spiele-Liste / Flohmarkt Bremer Spiele-Tage vom ' . date('d.m.Y'));
        $pdf->SetXY(12, 15);
        $pdf->Cell(40, 10, utf8_decode('Verkäufer: ' . $this->seller->getId() . ' - ' . $this->seller->getName()));
    }
}
