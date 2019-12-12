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
}
