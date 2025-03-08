<?php /** @noinspection PhpUnused */

use Noodlehaus\Config;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface as Logger;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator as v;
use Slim\Routing\RouteContext;

class CashpointController
{
    private Logger $logger;
    private Config $config;

    public function __construct(Logger $logger, Config $config)
    {
        $this->logger = $logger;
        $this->config = $config;
    }

    public function exportSellers(Request $request, Response $response)
    {
        $this->logger->debug('=== CashpointController:exportSellers(...) ===');

        if ($this->secretIsInvalid($request)) {
            return $response->withStatus(403);
        }

        $out = array();

        $sellers = SellerQuery::create()->find();
        foreach ($sellers as $seller) {
            $out[] = $seller->toFlatArray();
        }

        $response->getBody()->write(json_encode($out, JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    public function exportItems(Request $request, Response $response): Response
    {
        $this->logger->debug('=== CashpointController:exportItems(...) ===');

        if ($this->secretIsInvalid($request)) {
            return $response->withStatus(403);
        }

        $out = array();

        $items = ItemQuery::create()->find();
        foreach ($items as $item) {
            $id = $item->getId();
            $out[] = $item->toFlatArray();
        }

        $response->getBody()->write(json_encode($out, JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    public function confirmTransfer(Request $request, Response $response) : Response
    {
        $this->logger->debug('=== CashpointController:confirmTransfer(...) ===');

        if ($this->secretIsInvalid($request)) {
            return $response->withStatus(403);
        }

        $in = $request->getParsedBody();
        if ($in == null) {
            $this->logger->error('body is null');

            $response->getBody()->write(json_encode(['body' => 'missing'], JSON_PRETTY_PRINT));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $this->logger->debug('input', $in);

        if ($this->itemIdsNotAvailable($in)) {
            $this->logger->error('item_ids are not available');

            $response->getBody()->write(json_encode(['item_ids' => 'missing'], JSON_PRETTY_PRINT));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $transferTime = time();

        $out = ['item_ids' => ['transfered' => 0, 'unknown' => 0, 'invalid' => 0], 'timestamp' => $transferTime];

        foreach ($in['item_ids'] as $itemId) {
            if (v::intVal()->positive()->validate($itemId)) {
                $item = ItemQuery::create()->findOneById($itemId);
                if (isset($item)) {
                    $this->logger->info("item (id:$itemId) was transfered (time:$transferTime)");
                    $item->setTransfered($transferTime);
                    $item->save();
                    $out['item_ids']['transfered']++;
                } else {
                    $this->logger->error("item (id:$itemId) should be transfered (time:$transferTime) but was not found");
                    $out['item_ids']['unknown']++;
                }
            } else {
                $this->logger->error("submitted item id is not a positive number ($itemId)");
                $out['item_ids']['invalid']++;
            }
        }

        $this->logger->debug('output', $out);

        $response->getBody()->write(json_encode($out, JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    public function confirmSold(Request $request, Response $response) : Response
    {
        $this->logger->debug('=== CashpointController:confirmSold(...) ===');

        if ($this->secretIsInvalid($request)) {
            return $response->withStatus(403);
        }

        $in = $request->getParsedBody();
        if ($in == null) {
            $this->logger->error('body is null');

            $response->getBody()->write(json_encode(['body' => 'missing'], JSON_PRETTY_PRINT));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $this->logger->debug('input', $in);

        if ($this->itemIdsNotAvailable($in)) {
            $this->logger->error('item_ids are not available');

            $response->getBody()->write(json_encode(['item_ids' => 'missing'], JSON_PRETTY_PRINT));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        if ($this->analogItemsNotAvailable($in)) {
            $this->logger->error('analog_items are not available');

            $response->getBody()->write(json_encode(['analog_items' => 'missing'], JSON_PRETTY_PRINT));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $transferTime = time();

        $out = ['item_ids' => ['sold' => 0, 'unknown' => 0, 'invalid' => 0], 'analog_items' => ['added' => 0, 'invalid' => 0], 'timestamp' => $transferTime];

        foreach ($in['item_ids'] as $itemId) {
            if (v::intVal()->positive()->validate($itemId)) {
                $item = ItemQuery::create()->findOneById($itemId);
                if (isset($item)) {
                    $this->logger->info("item (id:$itemId) was sold (time:$transferTime)");
                    $item->setSold($transferTime);
                    $item->save();
                    $out['item_ids']['sold']++;
                } else {
                    $this->logger->error("item (id:$itemId) should be sold (time:$transferTime) but was not found");
                    $out['item_ids']['unknown']++;
                }
            } else {
                $this->logger->error("submitted item id is not a positive number ($itemId)");
                $out['item_ids']['invalid']++;
            }
        }

        $nameValidator = v::alpha($this->config->get('validation.additionalAllowedChars'))->length(1, 128);
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
                $sellerName = $analogItemData['seller_name'] ?? 'MISSING';
                $itemName = $analogItemData['item_name'] ?? 'MISSING';
                $itemPrice = $analogItemData['item_price'] ?? 'MISSING';
                $this->logger->error("analog item is invalid ($sellerName|$itemName|$itemPrice)", $exception->getMessages());
                $out['analog_items']['invalid']++;
            }
        }

        $this->logger->debug('output', $out);

        $response->getBody()->write(json_encode($out, JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    private function secretIsInvalid(Request $request): bool
    {
        $validSecret = $this->config->get('cashpoint.secret');
        $submittedSecret = RouteContext::fromRequest($request)->getRoute()->getArgument('secret', 'null');
        $valid = v::equals($validSecret)->validate($submittedSecret);

        if (!$valid) {
            $this->logger->warn('invalid secret "' . $submittedSecret . '"');
        }

        return !$valid;
    }

    private function itemIdsNotAvailable($in): bool
    {
        return !v::key('item_ids', v::arrayType())->validate($in);
    }

    private function analogItemsNotAvailable($in): bool
    {
        return !v::key('analog_items', v::arrayType())->validate($in);
    }
}
