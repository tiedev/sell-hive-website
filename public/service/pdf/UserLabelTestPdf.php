<?php

class UserLabelTestPdf extends UserLabelPdf
{
    public function __construct($logger)
    {
        parent::__construct($logger, 'testrahmen');
    }

    public function generate(): string
    {
        $pdf = $this->initPdf();

        for ($this->currentIndex = 0; $this->currentIndex < self::ITEMS_PER_PAGE; $this->currentIndex++) {
            $pdf->SetDrawColor(41, 49, 51); // Anthrazitgrau
            $pdf->Rect($this->x(), $this->y(), $this->settings->getLabelWidth(), $this->settings->getLabelHeight());
        }

        return $this->closePdf($pdf);
    }
}
