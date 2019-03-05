<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Slim\Views\Twig as Twig;

class ItemManagerController
{
    public function show(Request $request, Response $response, Logger $logger, Twig $twig, ContextService $contextService)
    {
        $logger->debug('=== ItemManagerController:show(...) ===');

        $context = $contextService->getGlobal();

        $context['title'] = 'Spiele eingeben und verwalten';

        $context['img']['comment']['src'] = 'img/icons/comment.png';

        $context['img']['boxed_as_new']['src']['yes'] = 'img/icons/ice_cube.png';
        $context['img']['boxed_as_new']['src']['no'] = 'img/icons/ice_cube_inactive.png';
        $context['img']['boxed_as_new']['tooltip']['yes'] = 'Spiel ist ungeöffnet. (OVP)';
        $context['img']['boxed_as_new']['tooltip']['no'] = 'Spiel ist geöffnet.';

        $context['img']['donate']['src']['yes'] = 'img/icons/shopping.png';
        $context['img']['donate']['src']['no'] = 'img/icons/shopping_inactive.png';
        $context['img']['donate']['tooltip']['yes'] = 'Spiel wird nicht abgeholt.';
        $context['img']['donate']['tooltip']['no'] = 'Spiel wird abgeholt.';

        $context['img']['labeled']['src']['yes'] = 'img/icons/tag_blue.png';
        $context['img']['labeled']['src']['no'] = 'img/icons/tag_blue_inactive.png';
        $context['img']['labeled']['tooltip']['yes'] = 'Etikett wurde bereits erzeugt.';
        $context['img']['labeled']['tooltip']['no'] = 'Etikett wurde noch nicht erzeugt.';

        $context['img']['transfered']['src']['yes'] = 'img/icons/shop.png';
        $context['img']['transfered']['src']['no'] = 'img/icons/shop_inactive.png';
        $context['img']['transfered']['tooltip']['yes'] = 'Spiel abgegeben.';
        $context['img']['transfered']['tooltip']['no'] = 'Spiel nicht abgegeben.';

        $context['img']['sold']['src']['yes'] = 'img/icons/coins.png';
        $context['img']['sold']['src']['no'] = 'img/icons/coins_inactive.png';
        $context['img']['sold']['tooltip']['yes'] = 'Spiel wurde verkauft.';
        $context['img']['sold']['tooltip']['no'] = 'Spiel nicht verkauft.';

        $context['button']['add']['text'] = 'Spiel hinzufügen';
        $context['button']['add']['tooltip']['init'] = 'Bitte warten ...';
        $context['button']['add']['tooltip']['open'] = 'Eingabe bis {0} möglich.';
        $context['button']['add']['tooltip']['closed'] = 'Eingabe ist nicht mehr möglich.';

        $context['button']['edit'] = 'bearbeiten';
        $context['button']['unlabel'] = 'Etikett nochmal?';
        $context['button']['unlabelAll'] = 'alle Etiketten nochmal?';
        $context['button']['delete'] = 'löschen';
        $context['button']['deleteAll'] = 'alle Spiele löschen';
        $context['button']['openLabelModal'] = 'Etiketten erzeugen (PDF)';
        $context['button']['openItemListModal'] = 'Abgabeliste erzeugen (PDF)';

        return $twig->render($response, 'content/itemManager.twig', $context);
    }
}
