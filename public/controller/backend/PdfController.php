<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Respect\Validation\Validator as v;

class PdfController
{
    public function genLabelItemPdf(Request $request, Response $response, Logger $logger)
    {
        $logger->debug('=== PdfController:genLabelItemPdf(...) ===');

        if (!isset($_SESSION['user'])) {
            $logger->debug('no user session');
            return $response->withStatus(403);
        }

        $out = array();
        $out['valid'] = true;

        $in = $request->getParsedBody();

        if (is_array($in)) {
            $logger->debug('input', $in);
        } else {
            $logger->debug('parsed body is no array');
            return $response->withStatus(500);
        }

        if (!v::intVal()->between(0, UserLabelPdf::ITEMS_PER_PAGE - 1, true)->validate($in['startPosition'])) {
            $out['startPosition'] = 'invalid';
            $out['valid'] = false;
        }

        if (!v::intVal()->between(1, 3, true)->validate($in['multiplier'])) {
            $out['multiplier'] = 'invalid';
            $out['valid'] = false;
        }

        if ($out['valid']) {
            $itemPdf = new UserLabelItemPdf($logger);
            $itemPdf->setSeller($_SESSION['user']);
            $itemPdf->setStartIndex($in['startPosition']);
            $itemPdf->setMultiplier($in['multiplier']);
            $out['path'] = $itemPdf->generate();
        } else {
            $logger->debug('data invalid');
        }

        $logger->debug('output', $out);

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }

    public function genLabelTestPdf(Request $request, Response $response, Logger $logger)
    {
        $logger->debug('=== PdfController:genLabelTestPdf(...) ===');

        if (!isset($_SESSION['user'])) {
            $logger->debug('no user session');
            return $response->withStatus(403);
        }

        $out = array();

        $testPdf = new UserLabelTestPdf($logger);
        $testPdf->setSeller($_SESSION['user']);
        $out['path'] = $testPdf->generate();

        $logger->debug('output', $out);

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }

    public function getLabelSettings(Request $request, Response $response, Logger $logger)
    {
        $logger->debug('=== PdfController:getLabelSettings(...) ===');

        if (!isset($_SESSION['user'])) {
            $logger->debug('no user session');
            return $response->withStatus(403);
        }

        $settings = SellerPrintSettingsQuery::create()->getOneOrDefaultByFkSellerId($_SESSION['user']);

        $out = $settings->toFlatArray();

        $logger->debug('output', $out);

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }

    public function setLabelSettings(Request $request, Response $response, Logger $logger)
    {
        $logger->debug('=== PdfController:setLabelSettings(...) ===');

        if (!isset($_SESSION['user'])) {
            $logger->debug('no user session');
            return $response->withStatus(403);
        }

        $out = array();
        $out['valid'] = true;

        $in = $request->getParsedBody();
        if (is_array($in)) {
            $logger->debug('input', $in);
        } else {
            $logger->debug('parsed body is no array');
            return $response->withStatus(500);
        }

        if (!v::numeric()->between(1, 25, true)->validate($in['page_init_x'])) {
            $out['page_init_x'] = 'invalid';
            $out['valid'] = false;
        }

        if (!v::numeric()->between(1, 25, true)->validate($in['page_init_y'])) {
            $out['page_init_y'] = 'invalid';
            $out['valid'] = false;
        }

        if (!v::numeric()->between(0, 5, true)->validate($in['label_space_x'])) {
            $out['label_space_x'] = 'invalid';
            $out['valid'] = false;
        }

        if (!v::numeric()->between(0, 5, true)->validate($in['label_space_y'])) {
            $out['label_space_y'] = 'invalid';
            $out['valid'] = false;
        }

        if ($out['valid']) {
            $settings = SellerPrintSettingsQuery::create()->getOneOrDefaultByFkSellerId($_SESSION['user']);
            $logger->debug('settings default', $settings->toFlatArray());
            $settings->setPageInitX($in['page_init_x']);
            $settings->setPageInitY($in['page_init_y']);
            $settings->setLabelSpaceX($in['label_space_x']);
            $settings->setLabelSpaceY($in['label_space_y']);
            $settings->save();
        }

        $logger->debug('output', $out);

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }

    public function genItemList(Request $request, Response $response, Logger $logger)
    {
        $logger->debug('=== PdfController:genItemList(...) ===');

        if (!isset($_SESSION['user'])) {
            $logger->debug('no user session');
            return $response->withStatus(403);
        }

        $out = array();

        $itemListPdf = new ItemListPdf($logger);
        $itemListPdf->setSeller($_SESSION['user']);
        $itemListPdf->initItems();
        $out['path'] = $itemListPdf->generate();

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }
}
