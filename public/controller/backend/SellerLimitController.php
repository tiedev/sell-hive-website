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

    public function edit(Request $request, Response $response, Logger $logger, AuthService $auth, MailService $mail, InputValidationService $v)
    {
        $logger->debug('=== SellerLimitController:edit(...) ===');

        if ($auth->isNoAdmin()) {
            return $response->withStatus(403);
        }

        $in = $request->getParsedBody();
        if ($v->invalidEditSellerLimit($in)) {
            return $response->withStatus(400);
        }

        $seller = SellerQuery::create()->findOneById($in['id']);
        if ($seller == null) {
            $logger->error('seller (id:' . $in['id'] . ') does not exist');
            return $response->withStatus(404);
        }

        $out = array();
        $out['request_opened'] = $seller->getLimitRequest() > 0;
        $out['mailed'] = false;

        $seller->setLimit($in['new_limit']);
        $seller->setLimitRequest(null);
        $seller->save();

        if ($out['request_opened']) {
            $out['mailed'] = $mail->sendLimitInfoToSeller($seller);
        }

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }
}
