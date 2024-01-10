<?php /** @noinspection PhpUnused */

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Respect\Validation\Validator as v;
use Propel\Runtime\ActiveQuery\Criteria as c;
use Noodlehaus\Config;
use Slim\Routing\RouteContext;

class SellerLimitController
{
    private Logger $logger;
    private Config $config;
    private AuthService $auth;
    private MailService $mail;

    public function __construct(Logger $logger, Config $config, AuthService $auth, MailService $mail)
    {
        $this->logger = $logger;
        $this->config = $config;
        $this->auth = $auth;
        $this->mail = $mail;
    }

    public function get(Request $request, Response $response)
    {
        $this->logger->debug('=== SellerLimitController:get(...) ===');

        if ($this->auth->isNoUser()) {
            return $response->withStatus(403);
        }

        $out = array();

        $seller = SellerQuery::create()->requireOneById($_SESSION['user']);
        $out['current'] = $seller->getLimit();
        $out['requested'] = $seller->getLimitRequest();

        $response->getBody()->write(json_encode($out, JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    public function openRequest(Request $request, Response $response)
    {
        $this->logger->debug('=== SellerLimitController:openRequest(...) ===');

        if ($this->auth->isNoUser()) {
            return $response->withStatus(403);
        }

        $in = $request->getParsedBody();

        $this->logger->debug('input', $in);

        $out = array();
        $out['valid'] = true;
        $out['saved'] = false;
        $out['mailed'] = false;

        $maxStep = $this->config->get('seller.limit.maxStep');
        $seller = SellerQuery::create()->requireOneById($_SESSION['user']);
        $itemCount = $seller->countItems();

        if (!v::intVal()->positive()->between($itemCount, $itemCount + $maxStep)->validate($in['limit'])) {
            $out['valid'] = false;
        }

        if ($out['valid']) {
            $this->logger->debug('open limit request');

            $seller->setLimitRequest($in['limit']);
            $seller->save();

            $out['saved'] = true;
            $this->logger->debug('seller saved');

            $out['mailed'] = $this->mail->sendLimitRequestToAdmin($seller);
        } else {
            $this->logger->debug('data invalid');
        }

        $this->logger->debug('output', $out);

        $response->getBody()->write(json_encode($out, JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    public function resetLimits(Request $request, Response $response)
    {
        $this->logger->debug('=== SellerLimitController:resetLimits(...) ===');

        if ($this->secretIsInvalid($request, $this->config)) {
            return $response->withStatus(403);
        }

        $today = new DateTime('today');
        $sellers = SellerQuery::create()->filterByLimitTill(null, c::NOT_EQUAL)->find();
        foreach ($sellers as $seller) {
            $sellerName = $seller->getName();
            $sellerId = $seller->getId();
            $limitTill = $seller->getLimitTill();

            if ($limitTill < $today) {
                $limit = $seller->getLimit();
                $itemCount = ItemQuery::create()->filterBySeller($seller)->count();
                $seller->setLimit($itemCount);
                $seller->setLimitTill(null);
                $seller->save();

                $this->logger->info("Limit für $sellerName ($sellerId) von $limit auf $itemCount zurückgesetzt.");
            } else {
                $this->logger->debug("Limit für $sellerName ($sellerId) bis " . date_format($limitTill, 'Y-m-d') . " beibehalten.");
            }
        }

        return $response->withStatus(200);
    }

    private function secretIsInvalid(Request $request): bool
    {
        $validSecret = $this->config->get('seller.limit.secret');
        $submittedSecret = RouteContext::fromRequest($request)->getRoute()->getArgument('secret', 'null');
        $valid = v::equals($validSecret)->validate($submittedSecret);

        if (!$valid) {
            $this->logger->warn('invalid secret "' . $submittedSecret . '"');
        }

        return !$valid;
    }
}
