<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Slim\Views\Twig as Twig;

class BlockedPopUpModalController
{
    public function show(Request $request, Response $response, Logger $logger, Twig $twig, ContextService $contextService)
    {
        $logger->debug('=== BlockedPopUpModalController:show(...) ===');

        $context = $contextService->getGlobal();

        $in = $request->getParsedBody();

        // TODO validate pdfPath

        $context['pdfPath'] = $in['pdfPath'];

        return $twig->render($response, 'modal/blockedPopUp.twig', $context);
    }
}
