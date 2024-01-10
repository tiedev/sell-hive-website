<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface as Logger;
use Noodlehaus\Config;

class ConfigController
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function getWriteProtectionTime(Request $request, Response $response): Response
    {
        $this->logger->debug('=== ConfigController:getTimes(...) ===');

        // TODO : get from config file
        //$out = ConfigQuery::getTimestamp('WRITE_PROTECTION_TIME');
        $out = 'not implemented';

        $this->logger->debug('output', $out);

        $response->getBody()->write(json_encode($out, JSON_PRETTY_PRINT));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}
