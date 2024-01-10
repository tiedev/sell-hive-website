<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Slim\Views\Twig as Twig;
use function OpenApi\scan;

class SwaggerController
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
        $this->logger->debug('=== SwaggerController:show(...) ===');

        $context = $this->contextService->getGlobal();

        $context['swaggerJsonUrl'] = $context['baseUrl'] . '/swagger.json';

        return $this->twig->render($response, 'content/swagger.twig', $context);
    }

    public function config(Request $request, Response $response): Response
    {
        $this->logger->debug('=== SwaggerController:config(...) ===');

        $openapi = scan(['index.php', 'controller/backend', 'entity']);
        $response->getBody()->write($openapi->toYaml());

        return $response;
    }
}
