<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Slim\Views\Twig as Twig;

class SellerManagerController
{
    public function show(Request $request, Response $response, Logger $logger, Twig $twig, ContextService $contextService)
    {
        $logger->debug('=== StatisticController:show(...) ===');

        $context = $contextService->getGlobal();

        $context['title'] = 'Verkäufer verwalten';

        return $twig->render($response, 'content/SellerManager.twig', $context);
    }
}
