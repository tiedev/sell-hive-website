<?php

session_start();

require_once 'vendor/autoload.php';
require_once 'propel/config.php';

$app = new SellHiveApp();


// === Pages ===
$app->get('/', ['IndexController', 'show']);


// === Content ===
$app->get('/content/public', ['PublicController', 'show']);

$app->get('/content/itemManager', ['ItemManagerController', 'show']);

$app->get('/content/labelCreator', ['LabelCreatorController', 'show']);

$app->get('/content/itemListCreator', ['ItemListCreatorController', 'show']);

$app->get('/content/statistic', ['StatisticController', 'show']);


// === Modals ===
$app->post('/modal/blockedPopUpModal', ['BlockedPopUpModalController', 'show']);

$app->get('/modal/infoModal/{event}/{result}', ['InfoModalController', 'show']);

$app->get('/modal/itemEditorModal[/{itemId}]', ['ItemEditorModalController', 'show']);

$app->get('/modal/sureModal/{type}', ['SureModalController', 'show']);

$app->get('/modal/printSettings', ['PrintSettingsModalController', 'show']);


/** AuthController **/
// session for user exists?
$app->get('/backend/auth', ['AuthController', 'isAuthenticated']);

// create session when mail address and password are correct
$app->post('/backend/auth', ['AuthController', 'login']);

// destroy session from active user
$app->delete('/backend/auth', ['AuthController', 'logout']);

// generate new password for given mail address (and correct captcha)
$app->post('/backend/auth/remind', ['AuthController', 'remind']);


/** SellerController **/
// get seller statstic (admin only)
$app->get('/backend/sellers', ['SellerController', 'list']);

// get seller info for active user
$app->get('/backend/seller', ['SellerController', 'get']);

// create new seller
$app->post('/backend/seller', ['SellerController', 'create']);

// open limit request
$app->post('/backend/limit/open', ['SellerController', 'openLimitRequest']);

// close limit request (admin only)
$app->post('/backend/limit/close', ['SellerController', 'closeLimitRequest']);


/** ItemController **/
// count items for active user
$app->get('/backend/item/count', ['ItemController', 'getCount']);

// list items for active user
$app->get('/backend/item', ['ItemController', 'listItems']);

// create new item for active user
$app->post('/backend/item', ['ItemController', 'createItem']);

// get item for active user
$app->get('/backend/item/{id}', ['ItemController', 'getItem']);

// edit item for active user
$app->post('/backend/item/{id}', ['ItemController', 'editItem']);

// deletes item for active user
$app->delete('/backend/item/{id}', ['ItemController', 'deleteItem']);


/** PdfController **/
// create label pdf for active user
$app->post('/backend/pdf/label/item', ['PdfController', 'genLabelItemPdf']);

// create test label pdf for active user
$app->post('/backend/pdf/label/test', ['PdfController', 'genLabelTestPdf']);

// retrieve measurements for label pdf
$app->get('/backend/pdf/label/settings', ['PdfController', 'getLabelSettings']);

// set user specific measurements for label pdf
$app->post('/backend/pdf/label/settings', ['PdfController', 'setLabelSettings']);

// create item list pdf for active user
$app->get('/backend/pdf/list/item', ['PdfController', 'genItemList']);


/** ConfigController **/
// list sellers
$app->get('/backend/configuration/writeProtectionTime', ['ConfigController', 'getWriteProtectionTime']);


/** CashPointController **/
// list sellers
$app->get('/backend/cashpoint/export/sellers/{secret}', ['CashpointController', 'exportSellers']);

// list items
$app->get('/backend/cashpoint/export/items/{secret}', ['CashpointController', 'exportItems']);

// confirm items transfered
$app->post('/backend/cashpoint/confirm/transfer/{secret}', ['CashpointController', 'confirmTransfer']);

// receipt for sold items
$app->post('/backend/cashpoint/confirm/sold/{secret}', ['CashpointController', 'confirmSold']);


$app->run();
