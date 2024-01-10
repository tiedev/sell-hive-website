<?php

use Base\Seller as BaseSeller;

/**
 * Skeleton subclass for representing a row from the 'seller' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class Seller extends BaseSeller
{
    public function getBarcodeId(): string
    {
        $year = date("Y");
        $prefixOfTheYear = chr($year - 1953);
        return $prefixOfTheYear . $this->getId();
    }

    public function getName(): string
    {
        return $this->getFirstName() . ' ' . $this->getLastName();
    }

    public function initLimit($limit, $autoAccept, $limitTill): void
    {
        if ($limit > $autoAccept) {
            $this->setLimit($autoAccept);
            $this->setLimitRequest($limit);
        } else {
            $this->setLimit($limit);
            $this->setLimitRequest(null);
        }
        if (!empty($limitTill)) {
            $this->setLimitTill($limitTill);
        }
    }

    public function genPassword(): void
    {
        $this->setPassword(Seller::genSecret(8));
    }

    public function genPathSecret()
    {
        $this->setPathSecret(Seller::genSecret(32));
    }

    public function toFlatArray(): array
    {
        $array = array();
        $array['id'] = $this->getId();
        $array['barcode_id'] = $this->getBarcodeId();
        $array['name'] = $this->getName();
        return $array;
    }

    private static function genSecret($length): string
    {
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";

        $password = array();
        $alphabetMaxIndex = strlen($alphabet) - 1;
        for ($position = 0; $position < $length; $position++) {
            $alphabetRandomIndex = rand(0, $alphabetMaxIndex);
            $password[$position] = $alphabet[$alphabetRandomIndex];
        }
        return implode($password);
    }
}
