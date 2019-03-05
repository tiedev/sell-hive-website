<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;
use Noodlehaus\Config;

class CashpointController
{
    private $additionalChars = 'ÄÖÜäöüß/_+&.-';

    public function exportSellers(Request $request, Response $response, Logger $logger, Config $config)
    {
        $logger->debug('=== CashpointController:exportSellers(...) ===');

        if ($this->secretIsInvalid($request, $config)) {
            return $response->withStatus(403);
        }

        $out = array();

        $sellers = SellerQuery::create()->find();
        foreach ($sellers as $seller) {
            $out[] = $seller->toFlatArray();
        }

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }

    public function exportItems(Request $request, Response $response, Logger $logger, Config $config)
    {
        $logger->debug('=== CashpointController:exportItems(...) ===');

        if ($this->secretIsInvalid($request, $config)) {
            return $response->withStatus(403);
        }

        $out = array();

        $items = ItemQuery::create()->find();
        foreach ($items as $item) {
            $id = $item->getId();
            $out[] = $item->toFlatArray();
        }

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }

    public function confirmTransfer(Request $request, Response $response, Logger $logger, Config $config)
    {
        $logger->debug('=== CashpointController:confirmTransfer(...) ===');

        if ($this->secretIsInvalid($request, $config)) {
            return $response->withStatus(403);
        }

        $in = $request->getParsedBody();

        $logger->debug('input', isset($in) ? $in : array());

        if ($this->itemIdsNotAvailable($in)) {
            $logger->error('item_ids invalid');
            return $response->withJson([ 'item_ids' => 'invalid'], 400, JSON_PRETTY_PRINT);
        }

        $transferTime = time();

        $out = ['item_ids' => ['transfered' => 0, 'unknown' => 0, 'invalid' => 0], 'timestamp' => $transferTime];

        foreach ($in['item_ids'] as $itemId) {
            if (v::intVal()->positive()->validate($itemId)) {
                $item = ItemQuery::create()->findOneById($itemId);
                if (isset($item)) {
                    $logger->info("item (id:$itemId) was transfered (time:$transferTime)");
                    $item->setTransfered($transferTime);
                    $item->save();
                    $out['item_ids']['transfered']++;
                } else {
                    $logger->error("item (id:$itemId) should be transfered (time:$transferTime) but was not found");
                    $out['item_ids']['unknown']++;
                }
            } else {
                $logger->error("submitted item id is not a positive number ($itemId)");
                $out['item_ids']['invalid']++;
            }
        }

        $logger->debug('output', $out);

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }

    public function confirmSold(Request $request, Response $response, Logger $logger, Config $config)
    {
        $logger->debug('=== CashpointController:confirmSold(...) ===');

        if ($this->secretIsInvalid($request, $config)) {
            return $response->withStatus(403);
        }

        $in = $request->getParsedBody();

        $logger->debug('input', isset($in) ? $in : array());

        if ($this->itemIdsNotAvailable($in)) {
            $logger->error('item_ids are not available');
            return $response->withJson([ 'item_ids' => 'missing'], 400, JSON_PRETTY_PRINT);
        }

        if ($this->analogItemsNotAvailable($in)) {
            $logger->error('analog_items are not available');
            return $response->withJson([ 'analog_items' => 'missing'], 400, JSON_PRETTY_PRINT);
        }

        $transferTime = time();

        $out = ['item_ids' => ['sold' => 0, 'unknown' => 0, 'invalid' => 0], 'analog_items' => ['added' => 0, 'invalid' => 0], 'timestamp' => $transferTime];

        foreach ($in['item_ids'] as $itemId) {
            if (v::intVal()->positive()->validate($itemId)) {
                $item = ItemQuery::create()->findOneById($itemId);
                if (isset($item)) {
                    $logger->info("item (id:$itemId) was sold (time:$transferTime)");
                    $item->setSold($transferTime);
                    $item->save();
                    $out['item_ids']['sold']++;
                } else {
                    $logger->error("item (id:$itemId) should be sold (time:$transferTime) but was not found");
                    $out['item_ids']['unknown']++;
                }
            } else {
                $logger->error("submitted item id is not a positive number ($itemId)");
                $out['item_ids']['invalid']++;
            }
        }

        $nameValidator = v::alpha($this->additionalChars)->length(1, 128);
        $priceValidator = v::intVal()->between(100, 10000, true)->multiple(50);
        $analogItemDataValidator = v::allOf(v::key('seller_name', $nameValidator), v::key('item_name', $nameValidator), v::key('item_price', $priceValidator));

        foreach ($in['analog_items'] as $analogItemData) {
            try {
                $analogItemDataValidator->assert($analogItemData);
                $analogItem = new AnalogItem();
                $analogItem->setSellerName($analogItemData['seller_name']);
                $analogItem->setItemName($analogItemData['item_name']);
                $analogItem->setItemPrice($analogItemData['item_price']);
                $analogItem->setSold($transferTime);
                $analogItem->save();
                $out['analog_items']['added']++;
            } catch (NestedValidationException $exception) {
                $sellerName = isset($analogItemData['seller_name']) ? $analogItemData['seller_name'] : 'MISSING';
                $itemName = isset($analogItemData['item_name']) ? $analogItemData['item_name'] : 'MISSING';
                $itemPrice = isset($analogItemData['item_price']) ? $analogItemData['item_price'] : 'MISSING';
                $logger->error("analog item  is invalid ($sellerName|$itemName|$itemPrice)", $exception->getMessages());
                $out['analog_items']['invalid']++;
            }
        }

        $logger->debug('output', $out);

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }

    private function secretIsInvalid(Request $request, Config $config)
    {
        $validSecret = $config->get('cashpoint.secret');
        $submittedSecret = $request->getAttribute('route')->getArgument('secret', 'null');
        $valid = v::equals($validSecret)->validate($submittedSecret);

        if (!$valid) {
            $logger->warn('invalid secret "' . $submittedSecret . '"');
        }

        return !$valid;
    }

    private function itemIdsNotAvailable($in)
    {
        return !v::key('item_ids', v::arrayType())->validate($in);
    }

    private function analogItemsNotAvailable($in)
    {
        return !v::key('analog_items', v::arrayType())->validate($in);
    }
}
