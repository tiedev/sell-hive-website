<?php

class UserLabelItemPdf extends UserLabelPdf
{
    private $startIndex;
    private $items;
    private $multiplier;

    public function __construct($logger)
    {
        parent::__construct($logger, 'etiketten');
    }

    public function setSeller($id): void
    {
        parent::setSeller($id);

        $this->logger->debug('load labelable items');
        $this->items = ItemQuery::create()->filterByFkSellerId($this->seller->getId())->find();
    }

    public function setStartIndex($startIndex): void
    {
        $this->startIndex = $startIndex;
    }

    public function setMultiplier($multiplier): void
    {
        $this->multiplier = $multiplier;
    }

    public function generate(): string
    {
        $pdf = $this->initPdf();

        $this->currentIndex = $this->startIndex;
        foreach ($this->items as $item) {
            if ($item->isLabeled()) {
                continue;
            }

            for ($multiCount = 0; $multiCount < $this->multiplier; $multiCount++) {
                if ($this->currentIndex >= self::ITEMS_PER_PAGE) {
                    $pdf->AddPage();
                    $this->currentIndex = 0;
                }

                $pdf->Rect($this->x(), $this->y(), $this->settings->getLabelWidth(), $this->settings->getLabelHeight());
                $pdf->SetXY($this->x() + 1, $this->y() + 1);
                $pdf->SetFont('Arial', 'B', 11);
                $pdf->MultiCell(62, 4, $item->getNameForPdf(), 0, 'L');
                $pdf->SetFont('Arial', 'B', 14);
                $pdf->SetXY($this->x() + 22, $this->y() + 12);
                $pdf->Cell(40, 10, $item->getPriceForPdf(), 0, 0, 'R');
                $pdf->SetFont('Arial', '', 10);
                $pdf->SetXY($this->x() + 22, $this->y() + 16);
                $pdf->Cell(40, 10, '[' . $this->seller->getId() . ']', 0, 0, 'R');
                $pdf->Code39($this->x() + 2, $this->y() + 19, $item->getBarcodeId(), 1, 9);
                $this->currentIndex++;
            }

            $now = new DateTime();
            $item->setLabeled($now->getTimestamp());
            $item->save();
        }

        return $this->closePdf($pdf);
    }
}
