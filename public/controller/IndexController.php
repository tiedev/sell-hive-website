<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface as Logger;
use Slim\Views\Twig as Twig;

class IndexController
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
        $this->logger->debug('=== IndexController:show(...) ===');

        $context = $this->contextService->getGlobal();

        $context['site']['title'] = 'Bremer Spiele-Tage - Flohmarkt';
        $context['site']['name'] = 'sellhive.tealtoken.de';

        $context['nav']['itemManager'] = 'Spiele';
        $context['nav']['labelCreator'] = 'Etiketten';
        $context['nav']['itemListCreator'] = 'Abgabe';

        $context['nav']['sellerManager'] = 'Verkäufer';
        $context['nav']['itemTable'] = 'Suche';

        $context['nav']['logout'] = 'logout';

        return $this->twig->render($response, 'index.twig', $context);
    }
}
