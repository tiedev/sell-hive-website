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

        $context['amazonLabelLink'] = 'https://www.amazon.de/gp/product/B0002S48UI/ref=as_li_tl?ie=UTF8&camp=1638&creative=6742&creativeASIN=B0002S48UI&linkCode=as2&tag=erxzdgwf-21&linkId=77fdaa42ed20cb4b98c029abbc62af50';
        $context['amazonLabelImg'] = '//ws-eu.amazon-adsystem.com/widgets/q?_encoding=UTF8&MarketPlace=DE&ASIN=B0002S48UI&ServiceVersion=20070822&ID=AsinImage&WS=1&Format=_SL250_&tag=erxzdgwf-21';
        $context['amazonLabelPing'] = '//ir-de.amazon-adsystem.com/e/ir?t=erxzdgwf-21&l=am2&o=3&a=B0002S48UI';

        return $this->twig->render($response, 'content/labelCreator.twig', $context);
    }
}
