<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Respect\Validation\Validator as v;
use Propel\Runtime\ActiveQuery\Criteria as c;
use Noodlehaus\Config;

class SellerController
{
    private $additionalChars = 'ÄÖÜäöüß-';

    public function create(Request $request, Response $response, Logger $logger, MailService $mailService)
    {
        $logger->debug('=== SellerController:create(...) ===');

        $out = array();
        $out['valid'] = true;
        $out['saved'] = false;
        $out['mailed'] = false;

        $in = $request->getParsedBody();

        $logger->debug('input', $in);

        if (!v::alpha($this->additionalChars)->length(1, 64)->validate($in['lastName'])) {
            $out['lastName'] = 'invalid';
            $out['valid'] = false;
        }

        if (!v::alpha($this->additionalChars)->length(1, 64)->validate($in['firstName'])) {
            $out['firstName'] = 'invalid';
            $out['valid'] = false;
        }

        if (!v::email()->length(1, 254)->validate($in['mail'])) {
            $out['mail'] = 'invalid';
            $out['valid'] = false;
        }

        $existingSeller = SellerQuery::create()->findOneByMail($in['mail']);
        if ($existingSeller != null) {
            $out['mail'] = 'exists';
            $out['valid'] = false;
        }

        if ($out['valid']) {
            $logger->debug('save seller');

            $seller = new Seller();
            $seller->setLastName($in['lastName']);
            $seller->setFirstName($in['firstName']);
            $seller->setMail($in['mail']);
            $seller->genPassword();
            $seller->genPathSecret();
            $seller->save();

            $out['saved'] = true;
            $logger->debug('seller saved');

            $mailService->mailNewSeller($seller);
            $out['mailed'] = $mailService->getMailedSuccessfully();
        } else {
            $logger->debug('data invalid');
        }

        $logger->debug('output', $out);

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }

    public function get(Request $request, Response $response, Logger $logger, AuthService $auth)
    {
        $logger->debug('=== SellerController:get(...) ===');

        if ($auth->isNoUser()) {
            return $response->withStatus(403);
        }

        $out = array();

        $seller = SellerQuery::create()->requireOneById($_SESSION['user']);
        $out['limit'] = $seller->getLimit();

        $logger->debug('output', $out);

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }

    public function list(Request $request, Response $response, Logger $logger, AuthService $auth)
    {
        $logger->debug('=== SellerController:list(...) ===');

        if ($auth->isNoAdmin()) {
            return $response->withStatus(403);
        }

        $out = array();

        $out['details'] = array();
        $out['sum']['limits']['actual'] = 0;
        $out['sum']['limits']['requested'] = 0;
        $out['sum']['items']['created'] = 0;
        $out['sum']['items']['labeled'] = 0;
        $out['sum']['items']['transfered'] = 0;
        $out['sum']['items']['sold'] = 0;

        $sellers = SellerQuery::create()->orderById(c::DESC)->find();
        foreach ($sellers as $seller) {
            $box = array();
            $box['last_name'] = $seller->getLastName();
            $box['first_name'] = $seller->getFirstName();
            $box['mail'] = $seller->getMail();
            $box['limit'] = $seller->getLimit();
            $box['limit_request'] = $seller->getLimitRequest();
            $box['created_at'] = $seller->getCreatedAt();

            $countBox = array();
            $countBox['created'] = ItemQuery::create()->filterBySeller($seller)->filterByLabeled(null, c::ISNULL)->count();
            $countBox['labeled'] = ItemQuery::create()->filterBySeller($seller)->filterByLabeled(null, c::ISNOTNULL)->filterByTransfered(null, c::ISNULL)->count();
            $countBox['transfered'] = ItemQuery::create()->filterBySeller($seller)->filterByTransfered(null, c::ISNOTNULL)->filterBySold(null, c::ISNULL)->count();
            $countBox['sold'] = ItemQuery::create()->filterBySeller($seller)->filterBySold(null, c::ISNOTNULL)->count();
            $box['count_items'] = $countBox;

            $out['sum']['limits']['actual'] += $box['limit'];
            $out['sum']['limits']['requested'] += $box['limit'];
            $out['sum']['items']['created'] += $countBox['created'];
            $out['sum']['items']['labeled'] += $countBox['labeled'];
            $out['sum']['items']['transfered'] += $countBox['transfered'];
            $out['sum']['items']['sold'] += $countBox['sold'];

            $out['details'][$seller->getId()] = $box;
        }

        $logger->debug('output', $out);

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }

    public function openLimitRequest(Request $request, Response $response, Logger $logger, AuthService $auth, MailService $mailService, Config $config)
    {
        $logger->debug('=== SellerController:openLimitRequest(...) ===');

        if ($auth->isNoUser()) {
            return $response->withStatus(403);
        }

        $in = $request->getParsedBody();

        $logger->debug('input', $in);

        $out = array();
        $out['valid'] = true;
        $out['saved'] = false;
        $out['mailed'] = false;

        $limitRange = $config->get('seller.limitRange');
        $seller = SellerQuery::create()->requireOneById($_SESSION['user']);
        $itemCount = $seller->countItems();

        if (!v::intVal()->positive()->between($itemCount, $itemCount + $limitRange)->validate($in['limit'])) {
            $out['valid'] = false;
        }

        if ($out['valid']) {
            $logger->debug('open limit request');

            $seller->setLimitRequest($in['limit']);
            $seller->save();

            $out['saved'] = true;
            $logger->debug('seller saved');

            $mailService->mailLimitRequestOpened($seller);
            $out['mailed'] = $mailService->getMailedSuccessfully();
        } else {
            $logger->debug('data invalid');
        }

        $logger->debug('output', $out);

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }

    public function closeLimitRequest(Request $request, Response $response, Logger $logger, AuthService $auth, InputValidationService $v)
    {
        $logger->debug('=== SellerController:closeLimitRequest(...) ===');

        if ($auth->isNoAdmin()) {
            return $response->withStatus(403);
        }

        $in = $request->getParsedBody();
        if ($v->invalidSellerLimitCloseRequest($in)) {
            return $response->withStatus(400);
        }

        $seller = SellerQuery::create()->findOneById($in['id']);
        if ($seller == null) {
            $logger->error('seller (id:' . $in['id'] . ') does not exist');
            return $response->withStatus(404);
        }

        if ($in['approved']) {
            // TODO implement
        } else {
            // TODO implement
        }

        return $response->withStatus(200);
    }
}
