<?php /** @noinspection PhpUnused */

use Noodlehaus\Config;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface as Logger;
use Respect\Validation\Validator as v;
use Slim\Routing\RouteContext;

class ItemController
{
    private Logger $logger;
    private Config $config;
    private AuthService $auth;

    public function __construct(Logger $logger, Config $config, AuthService $auth)
    {
        $this->logger = $logger;
        $this->config = $config;
        $this->auth = $auth;
    }

    /**
     * @OA\Get(
     *     path="/backend/item/count",
     *     summary="count items for active user",
     *     @OA\Response(
     *         response="200",
     *         description="successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/SellerItemStatistic")
     *     ),
     *     @OA\Response(
     *         response="403",
     *         description="no user session"
     *     )
     * )
     */
    public function getCount(Request $request, Response $response)
    {
        $this->logger->debug('=== ItemController:getCount(...) ===');

        if (!isset($_SESSION['user'])) {
            $this->logger->debug('no user session');
            return $response->withStatus(403);
        }

        $this->logger->debug('count items (user id : ' . $_SESSION['user'] . ')');

        $statistic = new Entity\SellerItemStatistic();

        $statistic->all = ItemQuery::create()->filterByFkSellerId($_SESSION['user'])->count();
        $statistic->boxed_as_new = ItemQuery::create()->filterByFkSellerId($_SESSION['user'])->filterByBoxedAsNew(true)->count();
        $statistic->labeled = ItemQuery::create()->filterByFkSellerId($_SESSION['user'])->filterByLabeled()->count();
        $statistic->transfered = ItemQuery::create()->filterByFkSellerId($_SESSION['user'])->filterByTransfered()->count();
        $statistic->sold = ItemQuery::create()->filterByFkSellerId($_SESSION['user'])->filterBySold()->count();

        $this->logger->debug('SellerItemStatistic', (array)$statistic);

        $response->getBody()->write(json_encode($statistic, JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * @OA\Get(
     *     path="/backend/item",
     *     summary="list items for active user",
     *     @OA\Response(
     *         response="200",
     *         description="successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Item")
     *         )
     *     )
     * )
     */
    public function listUserItems(Request $request, Response $response)
    {
        $this->logger->debug('=== ItemController:listUserItems(...) ===');

        if (!isset($_SESSION['user'])) {
            $this->logger->debug('no user session');
            return $response->withStatus(403);
        }

        $this->logger->debug('load items (user id : ' . $_SESSION['user'] . ')');

        $out = array();

        $items = ItemQuery::create()->filterByFkSellerId($_SESSION['user'])->find();

        $this->logger->debug('found ' . count($items) . ' items');

        foreach ($items as $item) {
            $out[] = new Entity\Item($item);
        }

        $this->logger->debug('output', $out);

        $out_json = json_encode($out, JSON_PRETTY_PRINT);

        $this->logger->debug('output (json)', array($out_json));

        $response->getBody()->write($out_json);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * @OA\Get(
     *     path="/backend/items",
     *     summary="list all items",
     *     @OA\Response(
     *         response="200",
     *         description="successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Item")
     *         )
     *     ),
     *     @OA\Response(
     *         response="403",
     *         description="no admin session"
     *     )
     * )
     */
    public function listAllItems(Request $request, Response $response)
    {
        $this->logger->debug('=== ItemController:listAllItems(...) ===');

        if ($this->auth->isNoAdmin()) {
            return $response->withStatus(403);
        }

        $out = array();

        $items = ItemQuery::create()->find();

        foreach ($items as $item) {
            $out[] = new Entity\Item($item);
        }

        $this->logger->debug('output', $out);

        $response->getBody()->write(json_encode($out, JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    public function getItem(Request $request, Response $response)
    {
        $this->logger->debug('=== ItemController:getItem(...) ===');

        if (!isset($_SESSION['user'])) {
            $this->logger->debug('no user session');
            return $response->withStatus(403);
        }

        $itemId = RouteContext::fromRequest($request)->getRoute()->getArgument('id');
        if (!v::intVal()->positive()->validate($itemId)) {
            $this->logger->debug('id ' . $itemId . ' is not valid');
            return $response->withStatus(400);
        }

        $this->logger->debug('load item (user id : ' . $_SESSION['user'] . ', item id : ' . $itemId . ')');

        $out = array();

        $item = ItemQuery::create()->filterByFkSellerId($_SESSION['user'])->findOneById($itemId);
        if (isset($item)) {
            $out = $item->toFlatArray();
        } else {
            return $response->withStatus(403);
        }

        $this->logger->debug('output', $out);

        $response->getBody()->write(json_encode($out, JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    public function createItem(Request $request, Response $response)
    {
        $this->logger->debug('=== ItemController:createItem(...) ===');

        if (!isset($_SESSION['user'])) {
            $this->logger->debug('no user session');
            return $response->withStatus(403);
        }

        $out = array();
        $out['valid'] = true;

        $in = $request->getParsedBody();

        $this->logger->debug('input', $in);

        if (!v::alnum($this->config->get('validation.additionalAllowedChars'))->length(1, 30)->validate($in['name'])) {
            $out['name'] = 'invalid';
            $out['valid'] = false;
        }

        if (!v::optional(v::alnum($this->config->get('validation.additionalAllowedChars'))->length(1, 20))->validate($in['publisher'])) {
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

        if (!v::optional(v::alnum($this->config->get('validation.additionalAllowedChars'))->length(0, 512))->validate($in['comment'])) {
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
            $this->logger->debug('save item');

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
            $this->logger->debug('data invalid');
        }

        $this->logger->debug('output', $out);

        $response->getBody()->write(json_encode($out, JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    public function editItem(Request $request, Response $response)
    {
        $this->logger->debug('=== ItemController:editItem(...) ===');

        if (!isset($_SESSION['user'])) {
            $this->logger->debug('no user session');
            return $response->withStatus(403);
        }

        $userId = $_SESSION['user'];

        $out = array();
        $out['valid'] = true;

        $in = $request->getParsedBody();

        if (is_array($in)) {
            $this->logger->debug('input', $in);
        } else {
            $this->logger->debug('parsed body is no array');
            return $response->withStatus(400);
        }

        $itemId = RouteContext::fromRequest($request)->getRoute()->getArgument('id');
        if (!v::intVal()->positive()->validate($itemId)) {
            $this->logger->debug('id ' . $itemId . ' is not valid');
            return $response->withStatus(400);
        }

        $item = ItemQuery::create()->filterByFkSellerId($userId)->findPK($itemId);
        if (!isset($item)) {
            $this->logger->debug('found no item with id ' . $itemId . ' for user ' . $userId);
            return $response->withStatus(403);
        } else {
            $this->logger->debug('loaded item with id ' . $itemId);
        }

        if (array_key_exists('name', $in)) {
            if (v::alnum($this->config->get('validation.additionalAllowedChars'))->length(1, 128)->validate($in['name'])) {
                $item->setName($in['name']);
            } else {
                $out['name'] = 'invalid';
                $out['valid'] = false;
            }
        }

        if (array_key_exists('publisher', $in)) {
            if (v::optional(v::alnum($this->config->get('validation.additionalAllowedChars'))->length(1, 128))->validate($in['publisher'])) {
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
            if (v::optional(v::alnum($this->config->get('validation.additionalAllowedChars'))->length(0, 512))->validate($in['comment'])) {
                $item->setComment($in['comment']);
            } else {
                $out['comment'] = 'invalid';
                $out['valid'] = false;
            }
        }

        if ($out['valid']) {
            $this->logger->debug('save item');
            $item->save();
            $out['item'] = $item->toFlatArray();
        } else {
            $this->logger->debug('data invalid');
        }

        $this->logger->debug('output', $out);

        $response->getBody()->write(json_encode($out, JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    /**
     * @OA\Get(
     *     path="/backend/item/{id}",
     *     summary="delete item for active user",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="item id",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *   ),
     *     @OA\Response(
     *         response="200",
     *         description="successful operation"
     *     ),
     *     @OA\Response(
     *         response="403",
     *         description="no user session"
     *     )
     * )
     */
    public function deleteItem(Request $request, Response $response): Response
    {
        $this->logger->debug('=== ItemController:deleteItem(...) ===');

        if (!isset($_SESSION['user'])) {
            $this->logger->debug('no user session');
            return $response->withStatus(403);
        }

        $itemId = RouteContext::fromRequest($request)->getRoute()->getArgument('id');

        $this->logger->debug('delete item (item id : ' . $itemId . ')');

        $item = ItemQuery::create()->findPK($itemId);
        $item->delete();

        $this->logger->debug('item deleted');

        return $response->withStatus(200);
    }
}
