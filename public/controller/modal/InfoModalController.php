<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Slim\Routing\RouteContext;
use Slim\Views\Twig as Twig;

class InfoModalController
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
        $this->logger->debug('=== InfoModalController:show(...) ===');

        // TODO validate args

        $context = $this->contextService->getGlobal();

        $event = RouteContext::fromRequest($request)->getRoute()->getArgument('event');
        $result = RouteContext::fromRequest($request)->getRoute()->getArgument('result');

        switch ($event . '.' . $result) {

          case 'register.success':
            $context['title'] = 'Herzlich willkommen!';
            $context['content'] = 'Deine Zugangsdaten wurden erfolgreich versandt. Prüfe bitte Dein Postfach und ggf. den Spam-Ordner.';
            break;

          case 'register.error':
            $context['title'] = 'Herzlich willkommen!';
            $context['content'] = 'Dein Benutzerkonto wurde erfoglreich angelegt. Leider gab es aber ein Problem beim Versenden der Zugangsdaten. Führe bitte bitte eimal die Passwort-Erinnerung aus, indem Du Dich mit Deiner E-Mail-Adresse und ohne Passwort anmeldest und dann den erscheinenden Button drückst.';
            break;

          case 'remind.success':
            $context['title'] = 'Passwort-Erinnerung';
            $context['content'] = 'Deine neuen Zugangsdaten wurden erfolgreich versandt. Prüfe bitte Dein Postfach und ggf. den Spam-Ordner.';
            break;

          case 'remind.error':
            $context['title'] = 'Passwort-Erinnerung';
            $context['content'] = 'Leider gab es ein Problem beim Versand der neuen Zugangsdaten. Die Benutzerkonten aus dem letzten Jahr wurden gelöscht. Du musst Dich also neu registrieren, falls Du dies noch nicht wieder gemacht hast. Ansonsten wende Dich bitte an folgende E-Mail-Adresse: sellhive@tealtoken.de';
            break;

          default:
            $context['title'] = '?';
            $context['content'] = '?';
            break;
        }

        $context['button']['close'] = 'schließen';

        return $this->twig->render($response, 'modal/info.twig', $context);
    }
}
