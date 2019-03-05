<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Slim\Views\Twig as Twig;

class ItemEditorModalController
{
    public function show(Request $request, Response $response, Logger $logger, Twig $twig, ContextService $contextService)
    {
        $logger->debug('=== ItemEditorModalController:show(...) ===');

        // TODO validate args

        $context = $contextService->getGlobal();

        $context['title']['new'] = 'Spiel hinzufügen';
        $context['title']['edit'] = 'Spiel bearbeiten';

        $context['name']['label'] = 'Spielename';
        $context['name']['help'] = 'gültige Zeichen (A-Za-z0-9 /_+&.-), max. 30 Zeichen';
        $context['name']['maxLength'] = '30';
        $context['name']['invalid'] = 'Es sind ausschließlich kleine und große Buchstaben, Zahlen, einige Sonderzeichen und Leerzeichen zulässig.';

        $context['publisher']['label'] = 'Verlag oder Autor';
        $context['publisher']['help'] = 'gültige Zeichen (A-Za-z0-9 /_+&.-), max. 20 Zeichen';
        $context['publisher']['maxLength'] = '20';
        $context['publisher']['invalid'] = 'Es sind ausschließlich kleine und große Buchstaben, Zahlen, einige Sonderzeichen und Leerzeichen zulässig.';

        $context['price']['label'] = 'Preis';
        $context['price']['help'] = 'min. 1 Euro, max. 100 Euro';
        $context['price']['maxLength'] = '6';
        $context['price']['invalid'] = 'Der Preis muss in Schritten von 50 Cent zwischen 1 Euro und 100 Euro liegen.';

        $context['donate']['label'] = 'Spenden?';
        $context['donate']['no'] = 'Nein, ich hole das Spiel ab.';
        $context['donate']['yes'] = 'Ja, ich hole das Spiel nicht ab.';

        $context['boxed_as_new']['label'] = 'Ist das Spiel Originalverpackt (OVP)?';
        $context['boxed_as_new']['no'] = 'Nein, das Spiel ist nicht mehr eingeschweißt.';
        $context['boxed_as_new']['yes'] = 'Ja, das Spiel ist noch eingeschweißt.';

        $context['comment']['label'] = 'Eigene Notizen';
        $context['comment']['help'] = 'optional, wird nicht aufs Label gedruckt';
        $context['comment']['invalid'] = 'error text for comment';

        $context['limit']['invalid'] = 'Du hast leider dein Limit an Spielen erreicht.';

        $context['submit']['new'] = 'hinzufügen';
        $context['submit']['edit'] = 'übernehmen';
        $context['cancel'] = 'abbrechen';

        $itemId = $request->getAttribute('route')->getArgument('itemId');
        if (is_numeric($itemId)) {
            $context['itemId'] = $itemId;
        } else {
            $context['itemId'] = 'new';
        }

        return $twig->render($response, 'modal/itemEditor.twig', $context);
    }
}
