<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Slim\Views\Twig as Twig;

class IndexController
{
    public function show(Request $request, Response $response, Logger $logger, Twig $twig, ContextService $contextService)
    {
        $logger->debug('=== IndexController:show(...) ===');

        $context = $contextService->getGlobal();

        $context['site']['title'] = 'Bremer Spiele-Tage - Flohmarkt';
        $context['site']['name'] = 'sellhive.tealtoken.de';

        $context['nav']['itemManager'] = 'Spiele';
        $context['nav']['labelCreator'] = 'Etiketten';
        $context['nav']['itemListCreator'] = 'Abgabe';

        $context['nav']['sellerManager'] = 'Verkäufer';
        $context['nav']['itemTable'] = 'Suche';

        $context['nav']['logout'] = 'logout';

        return $twig->render($response, 'index.twig', $context);
    }
}
