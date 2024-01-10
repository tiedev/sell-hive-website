<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Slim\Views\Twig as Twig;

class OpenLimitRequestModalController
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
        $this->logger->debug('=== OpenLimitRequestModalController:show(...) ===');

        $context = $this->contextService->getGlobal();

        $context['title'] = 'Mehr Spiele?';

        $context['limit']['label'] = 'Neues Wunschkontingent';
        $context['limit']['help'] = 'min. Anzahl eingetragene Spiele';
        $context['limit']['maxLength'] = '4';
        $context['limit']['invalid'] = 'Der Wert muss mindestens der Anzahl der eingetragenen Spiele entsprechen.';

        $context['submit'] = 'abschicken';
        $context['cancel'] = 'abbrechen';

        return $this->twig->render($response, 'modal/openLimitRequest.twig', $context);
    }
}
