<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Slim\Views\Twig as Twig;

class SellerEditorModalController
{
    public function show(Request $request, Response $response, Logger $logger, Twig $twig, ContextService $contextService)
    {
        $logger->debug('=== SellerEditorModalController:show(...) ===');

        // TODO validate args

        $context = $contextService->getGlobal();

        $context['title'] = 'Verkäufer bearbeiten';

        $context['limit']['label'] = 'Spielename';
        $context['limit']['help'] = 'gültige Zeichen (A-Za-z0-9 /_+&.-), max. 30 Zeichen';
        $context['limit']['maxLength'] = '4';
        $context['limit']['invalid'] = 'Es sind ausschließlich kleine und große Buchstaben, Zahlen, einige Sonderzeichen und Leerzeichen zulässig.';

        $context['submit'] = 'abschicken';
        $context['cancel'] = 'abbrechen';

        $context['sellerId'] = $request->getAttribute('route')->getArgument('sellerId');

        return $twig->render($response, 'modal/SellerEditor.twig', $context);
    }
}
