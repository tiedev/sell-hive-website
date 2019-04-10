<?php

use Psr\Log\LoggerInterface as Logger;
use Noodlehaus\Config;

class AuthService
{
    private $logger;
    private $config;

    public function __construct(Logger $logger, Config $config)
    {
        $this->logger = $logger;
        $this->config = $config;
    }

    public function isNoUser()
    {
        $isUserInSession = isset($_SESSION['user']);

        if ($isUserInSession) {
            $this->logger->debug('user session (ID:' . $_SESSION['user'] . ')');
            return false;
        } else {
            $this->logger->info('no user session');
            return true;
        }
    }

    public function isNoAdmin()
    {
        if ($this->isNoUser()) {
            return true;
        }

        if ($_SESSION['user'] == -1) {
            $this->logger->debug('is admin session');
            return false;
        } else {
            $this->logger->info('no admin session');
            return true;
        }
    }
}
