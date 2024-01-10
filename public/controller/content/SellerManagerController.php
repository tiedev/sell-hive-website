<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Slim\Views\Twig as Twig;

class SellerManagerController
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
        $this->logger->debug('=== SellerManagerController:show(...) ===');

        $context = $this->contextService->getGlobal();

        $context['title'] = 'Verkäufer verwalten';

        return $this->twig->render($response, 'content/sellerManager.twig', $context);
    }
}
