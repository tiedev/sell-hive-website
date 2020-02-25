<?php

session_start();

require_once 'vendor/autoload.php';
require_once 'propel/config.php';

use OpenApi\Annotations as OA;

/**
 * Swagger PHP: https://github.com/zircote/swagger-php
 * OpenAPI Specification: https://swagger.io/specification/
 *
 * @OA\Info(title="Sell-Hive API", version="1.0.0")
 */
$app = new SellHiveApp();


// === Pages ===
$app->get('/', ['IndexController', 'show']);


// === Content ===
$app->get('/content/public', ['PublicController', 'show']);

$app->get('/content/itemManager', ['ItemManagerController', 'show']);

$app->get('/content/labelCreator', ['LabelCreatorController', 'show']);

$app->get('/content/itemListCreator', ['ItemListCreatorController', 'show']);

$app->get('/content/sellerManager', ['SellerManagerController', 'show']);

$app->get('/content/itemTable', ['ItemTableController', 'show']);


// === Modals ===
$app->post('/modal/blockedPopUpModal', ['BlockedPopUpModalController', 'show']);

$app->get('/modal/infoModal/{event}/{result}', ['InfoModalController', 'show']);

$app->get('/modal/itemEditor[/{itemId}]', ['ItemEditorModalController', 'show']);

$app->get('/modal/sureModal/{type}', ['SureModalController', 'show']);

$app->get('/modal/printSettings', ['PrintSettingsModalController', 'show']);

$app->get('/modal/openLimitRequest', ['OpenLimitRequestModalController', 'show']);

$app->get('/modal/sellerEditor/{sellerId}', ['SellerEditorModalController', 'show']);


// === Swagger ===
$app->get('/api', ['SwaggerController', 'show']);

$app->get('/swagger.json', ['SwaggerController', 'config']);


// === AuthController ===
$app->get('/backend/auth', ['AuthController', 'isAuthenticated']);

$app->post('/backend/auth', ['AuthController', 'login']);

$app->delete('/backend/auth', ['AuthController', 'logout']);

$app->post('/backend/auth/remind', ['AuthController', 'remind']);


/** SellerController **/
// list sellers (admin only)
$app->get('/backend/sellers', ['SellerController', 'list']);

// get seller info for specific user (admin only)
$app->get('/backend/seller/{id:[0-9]+}', ['SellerController', 'get']);

// set seller limit (admin only)
$app->post('/backend/seller/{id:[0-9]+}', ['SellerController', 'edit']);

// create new seller
$app->post('/backend/seller', ['SellerController', 'create']);


/** SellerLimitController **/
// get item limit for active user
$app->get('/backend/seller/limit', ['SellerLimitController', 'get']);

// open limit request
$app->post('/backend/seller/limitRequest', ['SellerLimitController', 'openRequest']);

// reset limits if limit till is reached
$app->delete('/backend/seller/limit/{secret}', ['SellerLimitController', 'resetLimits']);


/** ItemController **/
$app->get('/backend/item/count', ['ItemController', 'getCount']);

$app->get('/backend/item', ['ItemController', 'listUserItems']);

$app->get('/backend/items', ['ItemController', 'listAllItems']);

// create new item for active user
$app->post('/backend/item', ['ItemController', 'createItem']);

// get item for active user
$app->get('/backend/item/{id}', ['ItemController', 'getItem']);

// edit item for active user
$app->post('/backend/item/{id}', ['ItemController', 'editItem']);

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

$app->get('/backend/pdf', ['PdfController', 'list']);


/** ConfigController **/
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
