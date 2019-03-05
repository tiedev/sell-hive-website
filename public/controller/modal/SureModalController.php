<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Slim\Views\Twig as Twig;

class SureModalController
{
    public function show(Request $request, Response $response, Logger $logger, Twig $twig, ContextService $contextService)
    {
        $logger->debug('=== SureModalController:show(...) ===');

        // TODO validate args

        $context = $contextService->getGlobal();

        $type = $request->getAttribute('route')->getArgument('type');
        
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

        $context['yes']= 'ja (Enter)';
        $context['no'] = 'nein (ESC)';

        return $twig->render($response, 'modal/sure.twig', $context);
    }
}
