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

    public function create(Request $request, Response $response, Logger $logger, MailService $mail, Config $config)
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

        $maxStep = $config->get('seller.limit.maxStep');
        if (!v::intVal()->positive()->between(1, $maxStep)->validate($in['limit'])) {
            $out['limit'] = 'invalid';
            $out['valid'] = false;
        }

        if (!v::boolVal()->trueVal()->validate($in['contract'])) {
            $out['contract'] = 'invalid';
            $out['valid'] = false;
        }

        if ($out['valid']) {
            $logger->debug('save seller');

            $seller = new Seller();
            $seller->setLastName($in['lastName']);
            $seller->setFirstName($in['firstName']);
            $seller->setMail($in['mail']);
            $seller->initLimit($in['limit'], $config->get('seller.limit.autoAccept'));
            $seller->genPassword();
            $seller->genPathSecret();
            $seller->save();

            $out['saved'] = true;
            $logger->debug('seller saved');

            $out['mailed'] = $mail->sendWelcomeToSeller($seller);

            if ($seller->getLimitRequest() > 0) {
                $logger->info('send limit request');
                $mail->sendLimitRequestToAdmin($seller);
            }
        } else {
            $logger->debug('data invalid');
        }

        $logger->debug('output', $out);

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }

    public function get(Request $request, Response $response, Logger $logger, AuthService $auth)
    {
        $logger->debug('=== SellerController:get(...) ===');

        if ($auth->isNoAdmin()) {
            return $response->withStatus(403);
        }

        $sellerId = $request->getAttribute('route')->getArgument('id');
        if (!v::intVal()->positive()->validate($sellerId)) {
            $logger->debug('id ' . $sellerId . ' is not valid');
            return $response->withStatus(400);
        }

        $out = array();

        $seller = SellerQuery::create()->requireOneById($sellerId);
        $out['id'] = $seller->getId();
        $out['last_name'] = $seller->getLastName();
        $out['first_name'] = $seller->getFirstName();
        $out['mail'] = $seller->getMail();
        $out['limit'] = $seller->getLimit();
        $out['limit_till'] = $seller->getLimitTill();
        $out['limit_request'] = $seller->getLimitRequest() ?: 0;

        $logger->debug('output', $out);

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }

    public function edit(Request $request, Response $response, Logger $logger, AuthService $auth, MailService $mail, InputValidationService $v)
    {
        $logger->debug('=== SellerController:edit(...) ===');

        if ($auth->isNoAdmin()) {
            return $response->withStatus(403);
        }

        $in = $request->getParsedBody();
        $messages = $v->invalidEditSeller($in);
        if ($messages) {
            // TODO : give messages to client
            return $response->withStatus(400);
        }

        $sellerId = $request->getAttribute('route')->getArgument('id');
        if (!v::intVal()->positive()->validate($sellerId)) {
            $logger->debug('id ' . $sellerId . ' is not valid');
            return $response->withStatus(400);
        }

        $seller = SellerQuery::create()->findOneById($sellerId);
        if ($seller == null) {
            $logger->error('seller (id:' . $sellerId . ') does not exist');
            return $response->withStatus(404);
        }

        $out = array();
        $out['limit_changed'] = $seller->getLimit() != $in['limit'];
        $out['mailed'] = false;

        $seller->setLimitRequest($in['limitRequest']);
        $seller->setLimit($in['limit']);
        $seller->setLimitTill($in['limitTill']);
        $seller->save();

        if ($out['limit_changed']) {
            $out['mailed'] = $mail->sendLimitInfoToSeller($seller);
        }

        $out['valid'] = true;
        $out['seller']['limit_request'] = $seller->getLimitRequest();
        $out['seller']['limit'] = $seller->getLimit();
        $out['seller']['limit_till'] = $seller->getLimitTill();

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
            $box['limit_till'] = $seller->getLimitTill();
            $box['limit_request'] = $seller->getLimitRequest() ?: 0;

            $countBox = array();
            $countBox['created'] = ItemQuery::create()->filterBySeller($seller)->filterByLabeled(null, c::ISNULL)->count();
            $countBox['labeled'] = ItemQuery::create()->filterBySeller($seller)->filterByLabeled(null, c::ISNOTNULL)->filterByTransfered(null, c::ISNULL)->count();
            $countBox['transfered'] = ItemQuery::create()->filterBySeller($seller)->filterByTransfered(null, c::ISNOTNULL)->filterBySold(null, c::ISNULL)->count();
            $countBox['sold'] = ItemQuery::create()->filterBySeller($seller)->filterBySold(null, c::ISNOTNULL)->count();
            $box['count_items'] = $countBox;

            $out['sum']['limits']['actual'] += $box['limit'];
            $out['sum']['limits']['requested'] += $box['limit_request'];
            $out['sum']['items']['created'] += $countBox['created'];
            $out['sum']['items']['labeled'] += $countBox['labeled'];
            $out['sum']['items']['transfered'] += $countBox['transfered'];
            $out['sum']['items']['sold'] += $countBox['sold'];

            $out['details'][$seller->getId()] = $box;
        }

        $logger->debug('output', $out);

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }
}
