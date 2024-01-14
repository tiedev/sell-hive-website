<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Slim\Views\Twig as Twig;

class LabelCreatorController
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

    public function show(Request $request, Response $response)
    {
        $this->logger->debug('=== LabelCreatorController:show(...) ===');

        $context = $this->contextService->getGlobal();

        $context['title'] = 'Etiketten für den Druck erzeugen';

        $context['label']['startingPosition'] = 'Bei diesem Label mit dem Druck beginnen:';
        $context['label']['multiplier'] = 'Wieviele Label je Spiel erzeugen?';

        $context['multiplier']['option']['1'] = 'ein Label je Spiel';
        $context['multiplier']['option']['2'] = 'zwei Label je Spiel';
        $context['multiplier']['option']['3'] = 'drei Label je Spiel';

        $context['invalid']['startingPosition'] = 'error text for startingPosition';

        $context['amazonLabelLink'] = 'https://amzn.to/3TYKTtb';
        $context['amazonLabelImg'] = 'https://www.avery-zweckform.com/sites/default/files/styles/scale_1_1_ratio_style/public/avery_importer/template/lineart/L4737REV-25_4004182047378_line.jpg?itok=8mKe9HtX';

        $context['amazonLabelLink2'] = 'https://amzn.to/4aWAUdU';
        $context['amazonLabelImg2'] = 'https://m.media-amazon.com/images/S/aplus-media-library-service-media/3c119ff8-c7d0-4558-85b6-afe95049c180.__CR0,0,300,400_PT0_SX300_V1___.jpg';

        return $this->twig->render($response, 'content/labelCreator.twig', $context);
    }
}
