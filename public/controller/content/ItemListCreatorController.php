<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Slim\Views\Twig as Twig;

class ItemListCreatorController
{
    public function show(Request $request, Response $response, Logger $logger, Twig $twig, ContextService $contextService)
    {
        $logger->debug('=== ItemListCreatorController:show(...) ===');

        $context = $contextService->getGlobal();

        $context['button'] = 'Spieleliste erzeugen (PDF)';

        $context['link']['text'] = 'Vorlagen herunterladen (PDF)';
        $context['link']['url'] = 'data/Flohmarkt20-Datenblatt.und.Beleg.pdf';

        return $twig->render($response, 'content/itemListCreator.twig', $context);
    }
}
