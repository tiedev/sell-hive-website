<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Slim\Views\Twig as Twig;

class ItemListCreatorController
{
    private ContextService $contextService;
    private Logger $logger;
    private Twig $twig;

    public function __construct(ContextService $contextService, Logger $logger, Twig $twig)
    {
        $this->contextService = $contextService;
        $this->logger = $logger;
        $this->twig = $twig;
    }

    public function show(Request $request, Response $response): Response
    {
        $this->logger->debug('=== ItemListCreatorController:show(...) ===');

        $context = $this->contextService->getGlobal();

        $context['button'] = 'Spieleliste erzeugen (PDF)';

        $context['link']['text'] = 'Vorlagen herunterladen (PDF)';
        $context['link']['url'] = 'data/Flohmarkt-Datenblatt_und_Beleg.pdf';

        return $this->twig->render($response, 'content/itemListCreator.twig', $context);
    }
}
