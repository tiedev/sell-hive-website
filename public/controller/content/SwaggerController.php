<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Slim\Views\Twig as Twig;

class SwaggerController
{
    public function show(Request $request, Response $response, Logger $logger, Twig $twig, ContextService $contextService)
    {
        $logger->debug('=== SwaggerController:show(...) ===');

        $context = $contextService->getGlobal();

        $context['swaggerJsonUrl'] = $context['baseUrl'] . '/swagger.json';

        return $twig->render($response, 'content/swagger.twig', $context);
    }

    public function config(Request $request, Response $response, Logger $logger)
    {
        $logger->debug('=== SwaggerController:config(...) ===');

        $openapi = \OpenApi\scan('index.php');
        $response->getBody()->write($openapi->toYaml());

        return $response;
    }
}
