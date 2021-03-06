<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Slim\Views\Twig as Twig;

class ItemTableController
{
    public function show(Request $request, Response $response, Logger $logger, Twig $twig, ContextService $contextService)
    {
        $logger->debug('=== ItemTableController:show(...) ===');

        $context = $contextService->getGlobal();

        $context['title'] = 'Spiele';

        return $twig->render($response, 'content/itemTable.twig', $context);
    }
}
