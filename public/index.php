<?php

use DI\ContainerBuilder;
use Psr\Log\LoggerInterface as Logger;
use Slim\Factory\AppFactory;
use Noodlehaus\Config;

/**
 * Swagger PHP: https://github.com/zircote/swagger-php
 * OpenAPI Specification: https://swagger.io/specification/
 *
 * @OA\Info(title="Sell-Hive API", version="1.0.0")
 */

session_start();

require_once 'vendor/autoload.php';
require_once 'propel/config.php';

// init dependency injection (php-di)
$builder = new ContainerBuilder();
$builder->addDefinitions('config.php');
$container = $builder->build();

$app = AppFactory::createFromContainer($container);

$logger = $container->get(Logger::class);
$config = $container->get(Config::class);

$basePath = $config->get('common.basePath');
$displayErrorDetails = $config->get('slim.displayErrorDetails');
$logErrors = $config->get('slim.logErrors');
$logErrorDetails = $config->get('slim.logErrorDetails');

$logger->debug('config', [
    'basePath' => $basePath,
    'displayErrorDetails' => $displayErrorDetails,
    'logErrors' => $logErrors,
    'logErrorDetails' => $logErrorDetails
]);

$app->setBasePath($basePath);

$app->addRoutingMiddleware();

$app->addErrorMiddleware($displayErrorDetails, $logErrors, $logErrorDetails);

// === Pages ===
$app->get('/', IndexController::class . ':show');


// === Content ===
$app->get('/content/public', PublicController::class . ':show');

$app->get('/content/itemManager', ItemManagerController::class . ':show');

$app->get('/content/labelCreator', LabelCreatorController::class . ':show');

$app->get('/content/itemListCreator', ItemListCreatorController::class . ':show');

$app->get('/content/sellerManager', SellerManagerController::class . ':show');

$app->get('/content/itemTable', ItemTableController::class . ':show');


// === Modals ===
$app->post('/modal/blockedPopUpModal', BlockedPopUpModalController::class . ':show');

$app->get('/modal/infoModal/{event}/{result}', InfoModalController::class . ':show');

$app->get('/modal/itemEditor[/{itemId}]', ItemEditorModalController::class . ':show');

$app->get('/modal/sureModal/{type}', SureModalController::class . ':show');

$app->get('/modal/printSettings', PrintSettingsModalController::class . ':show');

$app->get('/modal/openLimitRequest', OpenLimitRequestModalController::class . ':show');

$app->get('/modal/sellerEditor/{sellerId}', SellerEditorModalController::class . ':show');


// === Swagger ===
$app->get('/api', SwaggerController::class . ':show');

$app->get('/swagger.json', SwaggerController::class . ':config');


// === AuthController ===
$app->get('/backend/auth', AuthController::class . ':isAuthenticated');

$app->post('/backend/auth', AuthController::class . ':login');

$app->delete('/backend/auth', AuthController::class . ':logout');

$app->post('/backend/auth/remind', AuthController::class . ':remind');


/** SellerController **/
// list sellers (admin only)
$app->get('/backend/sellers', SellerController::class . ':list');

// get seller info for specific user (admin only)
$app->get('/backend/seller/{id:[0-9]+}', SellerController::class . ':get');

// set seller limit (admin only)
$app->post('/backend/seller/{id:[0-9]+}', SellerController::class . ':edit');

// create new seller
$app->post('/backend/seller', SellerController::class . ':create');


/** SellerLimitController **/
// get item limit for active user
$app->get('/backend/seller/limit', SellerLimitController::class . ':get');

// open limit request
$app->post('/backend/seller/limitRequest', SellerLimitController::class . ':openRequest');

// reset limits if limit till is reached
$app->delete('/backend/seller/limit/{secret}', SellerLimitController::class . ':resetLimits');


/** ItemController **/
$app->get('/backend/item/count', ItemController::class . ':getCount');

$app->get('/backend/item', ItemController::class . ':listUserItems');

$app->get('/backend/items', ItemController::class . ':listAllItems');

// create new item for active user
$app->post('/backend/item', ItemController::class . ':createItem');

// get item for active user
$app->get('/backend/item/{id}', ItemController::class . ':getItem');

// edit item for active user
$app->post('/backend/item/{id}', ItemController::class . ':editItem');

$app->delete('/backend/item/{id}', ItemController::class . ':deleteItem');


/** PdfController **/
// create label pdf for active user
$app->post('/backend/pdf/label/item', PdfController::class . ':genLabelItemPdf');

// create test label pdf for active user
$app->post('/backend/pdf/label/test', PdfController::class . ':genLabelTestPdf');

// retrieve measurements for label pdf
$app->get('/backend/pdf/label/settings', PdfController::class . ':getLabelSettings');

// set user specific measurements for label pdf
$app->post('/backend/pdf/label/settings', PdfController::class . ':setLabelSettings');

// create item list pdf for active user
$app->get('/backend/pdf/list/item', PdfController::class . ':genItemList');

$app->get('/backend/pdf', PdfController::class . ':list');


/** ConfigController **/
$app->get('/backend/configuration/writeProtectionTime', ConfigController::class . ':getWriteProtectionTime');


/** CashPointController **/
// list sellers
$app->get('/backend/cashpoint/export/sellers/{secret}', CashpointController::class . ':exportSellers');

// list items
$app->get('/backend/cashpoint/export/items/{secret}', CashpointController::class . ':exportItems');

// confirm items transfered
$app->post('/backend/cashpoint/confirm/transfer/{secret}', CashpointController::class . ':confirmTransfer');

// receipt for sold items
$app->post('/backend/cashpoint/confirm/sold/{secret}', CashpointController::class . ':confirmSold');


$app->run();
