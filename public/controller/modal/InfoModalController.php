<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Slim\Views\Twig as Twig;

class InfoModalController
{
    public function show(Request $request, Response $response, Logger $logger, Twig $twig, ContextService $contextService)
    {
        $logger->debug('=== InfoModalController:show(...) ===');

        // TODO validate args

        $context = $contextService->getGlobal();

        $event = $request->getAttribute('route')->getArgument('event');
        $result = $request->getAttribute('route')->getArgument('result');

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

        return $twig->render($response, 'modal/info.twig', $context);
    }
}
