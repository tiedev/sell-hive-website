<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Slim\Views\Twig as Twig;

class PublicController
{
    public function show(Request $request, Response $response, Logger $logger, Twig $twig, ContextService $contextService)
    {
        $logger->debug('=== PublicController:show(...) ===');

        $context = $contextService->getGlobal();

        $context['register']['title'] = 'Registrieren';
        $context['register']['mail'] = 'E-Mail';
        $context['register']['name']['first'] = 'Vorname';
        $context['register']['name']['last'] = 'Nachname';
        $context['register']['limit'] = 'Wunschkontingent';
        $context['register']['contract'] = 'Vertrag gelesen und akzeptiert';
        $context['register']['submit'] = 'anmelden';

        $context['register']['exists']['mail'] = 'E-Mail-Adresse ist bereits registriert.';
        $context['register']['invalid']['mail'] = 'E-Mail-Adresse ist nicht gültig.';
        $context['register']['invalid']['name']['first'] = 'Vorname ist nicht gültig.';
        $context['register']['invalid']['name']['last'] = 'Nachname ist nicht gültig.';
        $context['register']['invalid']['limit'] = 'Wunschkontingent muss zwischen 1 und 50 liegen.';
        $context['register']['invalid']['contract'] = 'Vertrag muss gelesen und akzeptiert werden.';

        $context['login']['title'] = 'Login';
        $context['login']['mail'] = 'E-Mail';
        $context['login']['password'] = 'Passwort';
        $context['login']['submit'] = 'einloggen';
        $context['login']['remind'] = 'Passwort zusenden?';

        return $twig->render($response, 'content/public.twig', $context);
    }
}
