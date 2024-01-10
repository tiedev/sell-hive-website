<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Slim\Routing\RouteContext;
use Slim\Views\Twig as Twig;

class SureModalController
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
        $this->logger->debug('=== SureModalController:show(...) ===');

        // TODO validate args

        $context = $this->contextService->getGlobal();

        $type = RouteContext::fromRequest($request)->getRoute()->getArgument('type');

        switch ($type) {

            case 'unlabelItem':
                $context['title'] = 'Sicherheitsabfrage';
                $context['content'] = 'Spiel beim Erstellen von Etiketten wirklich erneut berücksichtigen?';
                break;

            case 'unlabelAllItems':
                $context['title'] = 'Sicherheitsabfrage';
                $context['content'] = 'Wirklich <b>alle</b> Spiele beim Erstellen von Etiketten erneut berücksichtigen?';
                break;

            case 'deleteItem':
                $context['title'] = 'Sicherheitsabfrage';
                $context['content'] = 'Spiel wirklich löschen?';
                break;

            default:
                $context['title'] = '?';
                $context['content'] = '?';
                break;
        }

        $context['yes'] = 'ja (Enter)';
        $context['no'] = 'nein (ESC)';

        return $this->twig->render($response, 'modal/sure.twig', $context);
    }
}
