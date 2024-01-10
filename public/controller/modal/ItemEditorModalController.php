<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Slim\Routing\RouteContext;
use Slim\Views\Twig as Twig;

class ItemEditorModalController
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
        $this->logger->debug('=== ItemEditorModalController:show(...) ===');
        $this->logger->debug('', array('attributes' => $request->getAttributes()));

        // TODO validate args

        $context = $this->contextService->getGlobal();

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

        $itemId = RouteContext::fromRequest($request)->getRoute()->getArgument('itemId');
        if (is_numeric($itemId)) {
            $context['itemId'] = $itemId;
        } else {
            $context['itemId'] = 'new';
        }

        return $this->twig->render($response, 'modal/itemEditor.twig', $context);
    }
}
