<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Slim\Views\Twig as Twig;

class PrintSettingsModalController
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
        $this->logger->debug('=== PrintSettingsModalController:show(...) ===');

        $context = $this->contextService->getGlobal();

        $context['title'] = 'Ausrichtung anpassen';

        $context['pageInit']['x']['label'] = 'Waagerechter Abstand zur Seite';
        $context['pageInit']['x']['help'] = 'Wert zwischen X mm und X mm';
        $context['pageInit']['x']['invalid'] = 'Der Wert muss zwischen X mm und X mm liegen.';
        $context['pageInit']['x']['img'] = 'img/PrintSettingsPageInitX.png';

        $context['pageInit']['y']['label'] = 'Senkrechter Abstand zur Seite';
        $context['pageInit']['y']['help'] = 'Wert zwischen X mm und X mm';
        $context['pageInit']['y']['invalid'] = 'Der Wert muss zwischen X mm und X mm liegen.';
        $context['pageInit']['y']['img'] = 'img/PrintSettingsPageInitY.png';

        $context['labelSpace']['x']['label'] = 'Waagerechter Abstand der Etiketten';
        $context['labelSpace']['x']['help'] = 'Wert zwischen X mm und X mm';
        $context['labelSpace']['x']['invalid'] = 'Der Wert muss zwischen X mm und X mm liegen.';
        $context['labelSpace']['x']['img'] = 'img/PrintSettingsLabelSpaceX.png';

        $context['labelSpace']['y']['label'] = 'Senkrechter Abstand der Etiketten';
        $context['labelSpace']['y']['help'] = 'Wert zwischen X mm und X mm';
        $context['labelSpace']['y']['invalid'] = 'Der Wert muss zwischen X mm und X mm liegen.';
        $context['labelSpace']['y']['img'] = 'img/PrintSettingsLabelSpaceY.png';

        $context['label']['height']['label'] = 'Höhe der Etiketten';
        $context['label']['height']['help'] = 'Wert zwischen X mm und X mm';
        $context['label']['height']['invalid'] = 'Der Wert muss zwischen X mm und X mm liegen.';
        $context['label']['height']['img'] = 'img/TODO.png';

        $context['label']['width']['label'] = 'Breite der Etiketten';
        $context['label']['width']['help'] = 'Wert zwischen X mm und X mm';
        $context['label']['width']['invalid'] = 'Der Wert muss zwischen X mm und X mm liegen.';
        $context['label']['width']['img'] = 'img/TODO.png';

        $context['submit'] = 'speichern';
        $context['cancel'] = 'abbrechen';

        return $this->twig->render($response, 'modal/printSettings.twig', $context);
    }
}
