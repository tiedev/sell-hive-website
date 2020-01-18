<?php

use Noodlehaus\Config;

class ContextService
{
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function getGlobal()
    {
        $protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
        $baseUrl = $protocol .'://' . $this->config->get('common.baseUrl');

        $context['baseUrl'] = $baseUrl;

        $context['backend']['url']['auth'] = $baseUrl . '/backend/auth';
        $context['backend']['url']['remind'] = $baseUrl . '/backend/auth/remind';
        $context['backend']['url']['item'] = $baseUrl . '/backend/item';
        $context['backend']['url']['items'] = $baseUrl . '/backend/items';
        $context['backend']['url']['itemCount'] = $baseUrl . '/backend/item/count';
        $context['backend']['url']['seller'] = $baseUrl . '/backend/seller';
        $context['backend']['url']['sellerLimitOpen'] = $baseUrl . '/backend/seller/limitRequest';
        $context['backend']['url']['sellerLimit'] = $baseUrl . '/backend/seller/limit';
        $context['backend']['url']['sellers'] = $baseUrl . '/backend/sellers';
        $context['backend']['url']['pdf']['labels'] = $baseUrl . '/backend/pdf/label/item';
        $context['backend']['url']['pdf']['test'] = $baseUrl . '/backend/pdf/label/test';
        $context['backend']['url']['pdf']['settings'] = $baseUrl . '/backend/pdf/label/settings';
        $context['backend']['url']['pdf']['itemlist'] = $baseUrl . '/backend/pdf/list/item';
        $context['backend']['url']['config']['writeProtectionTime'] = $baseUrl . '/backend/configuration/writeProtectionTime';

        $context['frontend']['content']['public'] = $baseUrl . '/content/public';
        $context['frontend']['content']['itemManager'] = $baseUrl . '/content/itemManager';
        $context['frontend']['content']['labelCreator'] = $baseUrl . '/content/labelCreator';
        $context['frontend']['content']['itemListCreator'] = $baseUrl . '/content/itemListCreator';
        $context['frontend']['content']['sellerManager'] = $baseUrl . '/content/sellerManager';
        $context['frontend']['content']['itemTable'] = $baseUrl . '/content/itemTable';

        $context['frontend']['modal']['blockedPopUp'] = $baseUrl . '/modal/blockedPopUpModal';
        $context['frontend']['modal']['info'] = $baseUrl . '/modal/infoModal';
        $context['frontend']['modal']['itemEditor'] = $baseUrl . '/modal/itemEditor';
        $context['frontend']['modal']['sure'] = $baseUrl . '/modal/sureModal';
        $context['frontend']['modal']['printSettings'] = $baseUrl . '/modal/printSettings';
        $context['frontend']['modal']['openLimitRequest'] = $baseUrl . '/modal/openLimitRequest';
        $context['frontend']['modal']['sellerEditor'] = $baseUrl . '/modal/sellerEditor';

        return $context;
    }
}
