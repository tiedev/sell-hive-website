<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Slim\Views\Twig as Twig;

class OpenLimitRequestModalController
{
    public function show(Request $request, Response $response, Logger $logger, Twig $twig, ContextService $contextService)
    {
        $logger->debug('=== OpenLimitRequestModalController:show(...) ===');

        $context = $contextService->getGlobal();

        $context['title'] = 'Mehr Spiele?';

        $context['limit']['label'] = 'Neues Wunschkontingent';
        $context['limit']['help'] = 'min. Anzahl eingetragene Spiele';
        $context['limit']['maxLength'] = '4';
        $context['limit']['invalid'] = 'Der Wert muss mindestens der Anzahl der eingetragenen Spiele entsprechen.';

        $context['submit'] = 'abschicken';
        $context['cancel'] = 'abbrechen';

        return $twig->render($response, 'modal/openLimitRequest.twig', $context);
    }
}
