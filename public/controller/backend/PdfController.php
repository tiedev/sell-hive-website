<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Respect\Validation\Validator as v;

class PdfController
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function genLabelItemPdf(Request $request, Response $response): Response
    {
        $this->logger->debug('=== PdfController:genLabelItemPdf(...) ===');

        if (!isset($_SESSION['user'])) {
            $this->logger->debug('no user session');
            return $response->withStatus(403);
        }

        $out = array();
        $out['valid'] = true;

        $in = $request->getParsedBody();

        if (is_array($in)) {
            $this->logger->debug('input', $in);
        } else {
            $this->logger->debug('parsed body is no array');
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
            $itemPdf = new UserLabelItemPdf($this->logger);
            $itemPdf->setSeller($_SESSION['user']);
            $itemPdf->setStartIndex($in['startPosition']);
            $itemPdf->setMultiplier($in['multiplier']);
            $out['path'] = $itemPdf->generate();
        } else {
            $this->logger->debug('data invalid');
        }

        $this->logger->debug('output', $out);

        $response->getBody()->write(json_encode($out, JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    public function genLabelTestPdf(Request $request, Response $response): Response
    {
        $this->logger->debug('=== PdfController:genLabelTestPdf(...) ===');

        if (!isset($_SESSION['user'])) {
            $this->logger->debug('no user session');
            return $response->withStatus(403);
        }

        $out = array();

        $testPdf = new UserLabelTestPdf($this->logger);
        $testPdf->setSeller($_SESSION['user']);
        $out['path'] = $testPdf->generate();

        $this->logger->debug('output', $out);

        $response->getBody()->write(json_encode($out, JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    public function getLabelSettings(Request $request, Response $response): Response
    {
        $this->logger->debug('=== PdfController:getLabelSettings(...) ===');

        if (!isset($_SESSION['user'])) {
            $this->logger->debug('no user session');
            return $response->withStatus(403);
        }

        $settings = SellerPrintSettingsQuery::create()->getOneOrDefaultByFkSellerId($_SESSION['user']);

        $out = $settings->toFlatArray();

        $this->logger->debug('output', $out);

        $response->getBody()->write(json_encode($out, JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    public function setLabelSettings(Request $request, Response $response): Response
    {
        $this->logger->debug('=== PdfController:setLabelSettings(...) ===');

        if (!isset($_SESSION['user'])) {
            $this->logger->debug('no user session');
            return $response->withStatus(403);
        }

        $out = array();
        $out['valid'] = true;

        $in = $request->getParsedBody();
        if (is_array($in)) {
            $this->logger->debug('input', $in);
        } else {
            $this->logger->debug('parsed body is no array');
            return $response->withStatus(500);
        }

        if (!v::number()->between(1, 25)->validate($in['page_init_x'])) {
            $out['page_init_x'] = 'invalid';
            $out['valid'] = false;
        }

        if (!v::number()->between(1, 25)->validate($in['page_init_y'])) {
            $out['page_init_y'] = 'invalid';
            $out['valid'] = false;
        }

        if (!v::number()->between(0, 5)->validate($in['label_space_x'])) {
            $out['label_space_x'] = 'invalid';
            $out['valid'] = false;
        }

        if (!v::number()->between(0, 5)->validate($in['label_space_y'])) {
            $out['label_space_y'] = 'invalid';
            $out['valid'] = false;
        }

        if (!v::number()->between(63, 64)->validate($in['label_width'])) {
            $out['label_width'] = 'invalid';
            $out['valid'] = false;
        }

        if (!v::number()->between(28, 30)->validate($in['label_height'])) {
            $out['label_height'] = 'invalid';
            $out['valid'] = false;
        }

        if ($out['valid']) {
            $settings = SellerPrintSettingsQuery::create()->getOneOrDefaultByFkSellerId($_SESSION['user']);
            $this->logger->debug('settings default', $settings->toFlatArray());
            $settings->setPageInitX($in['page_init_x']);
            $settings->setPageInitY($in['page_init_y']);
            $settings->setLabelSpaceX($in['label_space_x']);
            $settings->setLabelSpaceY($in['label_space_y']);
            $settings->setLabelWidth($in['label_width']);
            $settings->setLabelHeight($in['label_height']);
            $settings->save();
        }

        $this->logger->debug('output', $out);

        $response->getBody()->write(json_encode($out, JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    public function genItemList(Request $request, Response $response): Response
    {
        $this->logger->debug('=== PdfController:genItemList(...) ===');

        if (!isset($_SESSION['user'])) {
            $this->logger->debug('no user session');
            return $response->withStatus(403);
        }

        $out = array();

        $itemListPdf = new ItemListPdf($this->logger);
        $itemListPdf->setSeller($_SESSION['user']);
        $itemListPdf->initItems();
        $out['path'] = $itemListPdf->generate();

        $response->getBody()->write(json_encode($out, JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }

    public function list(Request $request, Response $response): Response
    {
        $this->logger->debug('=== PdfController:list(...) ===');

        if (!isset($_SESSION['user'])) {
            $this->logger->debug('no user session');
            return $response->withStatus(403);
        }

        $seller = SellerQuery::create()->findOneById($_SESSION['user']);
        $path = 'pdf/' . $seller->getPathSecret();
        $files = array_diff(scandir($path), array('.', '..'));

        $out = array();

        foreach ($files as $file) {
            $pdfFile = new Entity\PdfFile();
            $pdfFile->type = 'labels';
            $pdfFile->name = basename($file, '.pdf');
            $pdfFile->url = $path . '/' . $file;
            $out[] = $pdfFile;
        }

        $response->getBody()->write(json_encode($out, JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
