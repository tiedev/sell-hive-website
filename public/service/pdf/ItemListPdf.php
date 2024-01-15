<?php

class ItemListPdf extends UserPdf
{
    private $items;

    public function __construct($logger)
    {
        parent::__construct($logger, 'liste');
    }

    public function initItems(): void
    {
        $this->logger->debug('load all items for seller');
        $this->items = $this->seller->getItems();
    }

    public function generate(): string
    {
        $pdf = $this->initPdf();

        $x = 1;
        $i = 1;

        $this->addHeader($pdf);

        $fee = 0;
        $feeComposition = array();
        $feeComposition['80+'] = 0;
        $feeComposition['50+'] = 0;
        $feeComposition['20+'] = 0;
        $feeComposition['1+'] = 0;

        foreach ($this->items as $item) {
            if ($i % 2) {
                $pdf->Rect(0, 21 + $i * 6.5, 300, 6.5, 'F');
            }

            if ($item->getPrice() / 100 > 80) {
                $fee += 5.00;
                $feeComposition['80+']++;
            } else if ($item->getPrice() / 100 > 50) {
                $fee += 2.00;
                $feeComposition['50+']++;
            } else if ($item->getPrice() / 100 > 20) {
                $fee += 1.00;
                $feeComposition['20+']++;
            } else {
                $fee += 0.5;
                $feeComposition['1+']++;
            }

            $pdf->SetXY(12, 20 + $i * 6.5);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(40, 10, $x);

            $pdf->SetXY(20, 20 + $i * 6.5);
            $pdf->Cell(40, 10, $item->getId());

            $pdf->SetXY(40, 20 + $i * 6.5);
            $pdf->Cell(40, 10, $item->getNameForPdf());

            $pdf->SetXY(160, 20 + $i * 6.5);
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
        $pdf->Cell(40, 10,
            mb_convert_encoding('Einstellgebühr alle Spiele: ' . number_format($fee, 2, ',', '.'), 'ISO-8859-1', 'UTF-8') . ' '
            . chr(128) . ' (' . $feeComposition['1+'] . 'x0,50 '
            . chr(128) . ' / ' . $feeComposition['20+'] . 'x1,00 '
            . chr(128) . ' / ' . $feeComposition['50+'] . 'x2,00 '
            . chr(128) . ' / ' . $feeComposition['80+'] . 'x5,00 '
            . chr(128) . ')');

        return $this->closePdf($pdf);
    }

    private function addHeader($pdf): void
    {
        $pdf->SetXY(12, 10);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(40, 10, 'Spiele-Liste / Flohmarkt Bremer Spiele-Tage vom ' . date('d.m.Y'));
        $pdf->SetXY(12, 15);
        $pdf->Cell(40, 10, mb_convert_encoding('Verkäufer: ' . $this->seller->getId() . ' - ' . $this->seller->getName(), 'ISO-8859-1', 'UTF-8'));
    }
}
