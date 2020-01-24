<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Respect\Validation\Validator as v;
use Propel\Runtime\ActiveQuery\Criteria as c;
use Noodlehaus\Config;

class SellerLimitController
{
    public function get(Request $request, Response $response, Logger $logger, AuthService $auth, Config $config)
    {
        $logger->debug('=== SellerLimitController:get(...) ===');

        if ($auth->isNoUser()) {
            return $response->withStatus(403);
        }

        $out = array();

        $seller = SellerQuery::create()->requireOneById($_SESSION['user']);
        $out['current'] = $seller->getLimit();
        $out['requested'] = $seller->getLimitRequest();

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }

    public function openRequest(Request $request, Response $response, Logger $logger, AuthService $auth, MailService $mail, Config $config)
    {
        $logger->debug('=== SellerLimitController:openRequest(...) ===');

        if ($auth->isNoUser()) {
            return $response->withStatus(403);
        }

        $in = $request->getParsedBody();

        $logger->debug('input', $in);

        $out = array();
        $out['valid'] = true;
        $out['saved'] = false;
        $out['mailed'] = false;

        $maxStep = $config->get('seller.limit.maxStep');
        $seller = SellerQuery::create()->requireOneById($_SESSION['user']);
        $itemCount = $seller->countItems();

        if (!v::intVal()->positive()->between($itemCount, $itemCount + $maxStep)->validate($in['limit'])) {
            $out['valid'] = false;
        }

        if ($out['valid']) {
            $logger->debug('open limit request');

            $seller->setLimitRequest($in['limit']);
            $seller->save();

            $out['saved'] = true;
            $logger->debug('seller saved');

            $out['mailed'] = $mail->sendLimitRequestToAdmin($seller);
        } else {
            $logger->debug('data invalid');
        }

        $logger->debug('output', $out);

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }

    public function resetLimits(Request $request, Response $response, Logger $logger, Config $config)
    {
        $logger->debug('=== SellerLimitController:resetLimits(...) ===');

        if ($this->secretIsInvalid($request, $config)) {
            return $response->withStatus(403);
        }

        $today = new DateTime('today');
        $sellers = SellerQuery::create()->filterByLimitTill(null, c::NOT_EQUAL)->find();
        foreach ($sellers as $seller) {
            $sellerName = $seller->getName();
            $sellerId = $seller->getId();
            $limitTill = $seller->getLimitTill() ;

            if ($limitTill < $today) {
                $limit = $seller->getLimit();
                $itemCount = ItemQuery::create()->filterBySeller($seller)->count();
                $seller->setLimit($itemCount);
                $seller->setLimitTill(null);
                $seller->save();

                $logger->info("Limit für $sellerName ($sellerId) von $limit auf $itemCount zurückgesetzt.");
            } else {
                $logger->debug("Limit für $sellerName ($sellerId) bis " . date_format($limitTill, 'Y-m-d') . " beibehalten.");
            }
        }

        return $response->withStatus(200);
    }

    private function secretIsInvalid(Request $request, Config $config)
    {
        $validSecret = $config->get('seller.limit.secret');
        $submittedSecret = $request->getAttribute('route')->getArgument('secret', 'null');
        $valid = v::equals($validSecret)->validate($submittedSecret);

        if (!$valid) {
            $logger->warn('invalid secret "' . $submittedSecret . '"');
        }

        return !$valid;
    }
}
