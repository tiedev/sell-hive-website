<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Slim\Routing\RouteContext;
use Slim\Views\Twig as Twig;

class SellerEditorModalController
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
        $this->logger->debug('=== SellerEditorModalController:show(...) ===');

        // TODO validate args

        $context = $this->contextService->getGlobal();

        $context['title'] = 'Verkäufer bearbeiten';

        $context['limitRequest']['label'] = 'Limitwunsch';
        $context['limitRequest']['invalid'] = 'Es sind ausschließlich kleine und große Buchstaben, Zahlen, einige Sonderzeichen und Leerzeichen zulässig.';

        $context['limit']['label'] = 'Limit';
        $context['limit']['invalid'] = 'Es sind ausschließlich kleine und große Buchstaben, Zahlen, einige Sonderzeichen und Leerzeichen zulässig.';

        $context['limitTill']['label'] = 'gültig bis';
        $context['limitTill']['invalid'] = 'Es sind ausschließlich kleine und große Buchstaben, Zahlen, einige Sonderzeichen und Leerzeichen zulässig.';

        $context['submit'] = 'abschicken';
        $context['cancel'] = 'abbrechen';

        $context['sellerId'] = RouteContext::fromRequest($request)->getRoute()->getArgument('sellerId');

        return $this->twig->render($response, 'modal/SellerEditor.twig', $context);
    }
}
