<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Noodlehaus\Config;

class ConfigController
{
    public function getWriteProtectionTime(Request $request, Response $response, Logger $logger, Config $config)
    {
        $logger->debug('=== ConfigController:getTimes(...) ===');

        // TODO : get from config file
        //$out = ConfigQuery::getTimestamp('WRITE_PROTECTION_TIME');

        $logger->debug('output', $out);

        return $response->withJson($out, 200, JSON_PRETTY_PRINT);
    }
}
