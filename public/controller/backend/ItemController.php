<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Respect\Validation\Validator as v;

class ItemController
{
    private $additionalChars = 'ÄÖÜäöüß/_+&.-';

    public function getCount(Request $request, Response $response, Logger $logger)
    {
        $logger->debug('=== ItemController:listItems(...) ===');

        if (!isset($_SESSION['user'])) {
            $logger->debug('no user session');
            return $response->withStatus(403);
        }

        $logger->debug('count items (user id : ' . $_SESSION['user'] . ')');

        $out = array();

        $out['all'] = ItemQuery::create()->filterByFkSellerId($_SESSION['user'])->count();
        $out['boxed_as_new'] = ItemQuery::create()->filterByFkSellerId($_SESSION['user'])->filterByBoxedAsNew(true)->count();
        $out['labeled'] = ItemQuery::create()->filterByFkSellerId($_SESSION['user'])->filterByLabeled()->count();
        $out['transfered'] = ItemQuery::create()->filterByFkSellerId($_SESSION['user'])->filterByTransfered()->count();
        $out['sold'] = ItemQuery::create()->filterByFkSellerId($_SESSION['user'])->filterBySold()->count();

        $logger->debug('output', $out);

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }

    public function listUserItems(Request $request, Response $response, Logger $logger)
    {
        $logger->debug('=== ItemController:listUserItems(...) ===');

        if (!isset($_SESSION['user'])) {
            $logger->debug('no user session');
            return $response->withStatus(403);
        }

        $logger->debug('load items (user id : ' . $_SESSION['user'] . ')');

        $out = array();

        $items = ItemQuery::create()->filterByFkSellerId($_SESSION['user'])->find();

        foreach ($items as $item) {
            $out[] = $item->toFlatArray();
        }

        $logger->debug('output', $out);

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }

    public function listAllItems(Request $request, Response $response, Logger $logger, AuthService $auth)
    {
        $logger->debug('=== ItemController:listAllItems(...) ===');

        if ($auth->isNoAdmin()) {
            return $response->withStatus(403);
        }

        $out = array();

        $items = ItemQuery::create()->find();

        foreach ($items as $item) {
            $out[] = $item->toFlatArray();
        }

        $logger->debug('output', $out);

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }

    public function getItem(Request $request, Response $response, Logger $logger)
    {
        $logger->debug('=== ItemController:getItem(...) ===');

        if (!isset($_SESSION['user'])) {
            $logger->debug('no user session');
            return $response->withStatus(403);
        }

        $itemId = $request->getAttribute('route')->getArgument('id');
        if (!v::intVal()->positive()->validate($itemId)) {
            $logger->debug('id ' . $itemId . ' is not valid');
            return $response->withStatus(400);
        }

        $logger->debug('load item (user id : ' . $_SESSION['user'] . ', item id : ' . $itemId . ')');

        $out = array();

        $item = ItemQuery::create()->filterByFkSellerId($_SESSION['user'])->findOneById($itemId);
        if (isset($item)) {
            $out = $item->toFlatArray();
        } else {
            return $response->withStatus(403);
        }

        $logger->debug('output', $out);

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }

    public function createItem(Request $request, Response $response, Logger $logger)
    {
        $logger->debug('=== ItemController:createItem(...) ===');

        if (!isset($_SESSION['user'])) {
            $logger->debug('no user session');
            return $response->withStatus(403);
        }

        $out = array();
        $out['valid'] = true;

        $in = $request->getParsedBody();

        $logger->debug('input', $in);

        if (!v::alnum($this->additionalChars)->length(1, 30)->validate($in['name'])) {
            $out['name'] = 'invalid';
            $out['valid'] = false;
        }

        if (!v::optional(v::alnum($this->additionalChars)->length(1, 20))->validate($in['publisher'])) {
            $out['publisher'] = 'invalid';
            $out['valid'] = false;
        }

        if (!v::intVal()->between(100, 10000, true)->multiple(50)->validate($in['price'])) {
            $out['price'] = 'invalid';
            $out['valid'] = false;
        }

        if (!v::boolVal()->validate($in['boxed_as_new'])) {
            $out['boxed_as_new'] = 'invalid';
            $out['valid'] = false;
        }

        if (!v::optional(v::alnum($this->additionalChars)->length(0, 512))->validate($in['comment'])) {
            $out['comment'] = 'invalid';
            $out['valid'] = false;
        }

        $seller = SellerQuery::create()->findOneById($_SESSION['user']);
        if ($seller->countItems() >= $seller->getLimit()) {
            $out['limit'] = 'invalid';
            $out['valid'] = false;
        }

        // TODO check if adding is closed

        if ($out['valid']) {
            $logger->debug('save item');

            $item = new Item();
            $item->setFkSellerId($_SESSION['user']);
            $item->setName($in['name']);
            $item->setPublisher($in['publisher']);
            $item->setPrice($in['price']);
            $item->setBoxedAsNew($in['boxed_as_new']);
            $item->setComment($in['comment']);
            $item->save();

            $out['item'] = $item->toFlatArray();
        } else {
            $logger->debug('data invalid');
        }

        $logger->debug('output', $out);

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }

    public function editItem(Request $request, Response $response, Logger $logger)
    {
        $logger->debug('=== ItemController:editItem(...) ===');

        if (!isset($_SESSION['user'])) {
            $logger->debug('no user session');
            return $response->withStatus(403);
        }

        $userId = $_SESSION['user'];

        $out = array();
        $out['valid'] = true;

        $in = $request->getParsedBody();

        if (is_array($in)) {
            $logger->debug('input', $in);
        } else {
            $logger->debug('parsed body is no array');
            return $response->withStatus(400);
        }

        $itemId = $request->getAttribute('route')->getArgument('id');
        if (!v::intVal()->positive()->validate($itemId)) {
            $logger->debug('id ' . $itemId . ' is not valid');
            return $response->withStatus(400);
        }

        $item = ItemQuery::create()->filterByFkSellerId($userId)->findPK($itemId);
        if (!isset($item)) {
            $logger->debug('found no item with id ' . $itemId . ' for user ' . $userId);
            return $response->withStatus(403);
        } else {
            $logger->debug('loaded item with id ' . $itemId);
        }

        if (array_key_exists('name', $in)) {
            if (v::alnum($this->additionalChars)->length(1, 128)->validate($in['name'])) {
                $item->setName($in['name']);
            } else {
                $out['name'] = 'invalid';
                $out['valid'] = false;
            }
        }

        if (array_key_exists('publisher', $in)) {
            if (v::optional(v::alnum($this->additionalChars)->length(1, 128))->validate($in['publisher'])) {
                $item->setPublisher($in['publisher']);
            } else {
                $out['publisher'] = 'invalid';
                $out['valid'] = false;
            }
        }

        if (array_key_exists('price', $in)) {
            if (v::intVal()->between(100, 10000, true)->multiple(50)->validate($in['price'])) {
                $item->setPrice($in['price']);
            } else {
                $out['price'] = 'invalid';
                $out['valid'] = false;
            }
        }

        if (array_key_exists('boxed_as_new', $in)) {
            if (v::boolVal()->validate($in['boxed_as_new'])) {
                $item->setBoxedAsNew($in['boxed_as_new']);
            } else {
                $out['boxed_as_new'] = 'invalid';
                $out['valid'] = false;
            }
        }

        if (array_key_exists('unlabel', $in)) {
            if (v::boolVal()->validate($in['unlabel'])) {
                if ($in['unlabel']) {
                    $item->setLabeledToNull();
                }
            } else {
                $out['unlabel'] = 'invalid';
                $out['valid'] = false;
            }
        }

        if (array_key_exists('comment', $in)) {
            if (v::optional(v::alnum($this->additionalChars)->length(0, 512))->validate($in['comment'])) {
                $item->setComment($in['comment']);
            } else {
                $out['comment'] = 'invalid';
                $out['valid'] = false;
            }
        }

        if ($out['valid']) {
            $logger->debug('save item');
            $item->save();
            $out['item'] = $item->toFlatArray();
        } else {
            $logger->debug('data invalid');
        }

        $logger->debug('output', $out);

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }

    public function deleteItem(Request $request, Response $response, Logger $logger)
    {
        $logger->debug('=== ItemController:deleteItem(...) ===');

        if (!isset($_SESSION['user'])) {
            $logger->debug('no user session');
            return $response->withStatus(403);
        }

        $itemId = $request->getAttribute('route')->getArgument('id');

        $logger->debug('delete item (item id : ' . $itemId . ')');

        $item = ItemQuery::create()->findPK($itemId);
        $item->delete();

        $logger->debug('item deleted');

        return $response->withStatus(200);
    }
}
