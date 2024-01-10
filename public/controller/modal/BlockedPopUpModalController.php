<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Slim\Views\Twig as Twig;

class BlockedPopUpModalController
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
        $this->logger->debug('=== BlockedPopUpModalController:show(...) ===');

        $context = $this->contextService->getGlobal();

        $in = $request->getParsedBody();

        // TODO validate pdfPath

        $context['pdfPath'] = $in['pdfPath'];

        return $this->twig->render($response, 'modal/blockedPopUp.twig', $context);
    }
}
