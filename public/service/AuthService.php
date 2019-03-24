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
            $logger->trace('user session (ID:' . $_SESSION['user'] . ')');
            return true;
        } else {
            $logger->debug('no user session');
            return false;
        }
    }

    public function isNoAdmin()
    {
        if ($this->isNoUser()) {
            return true;
        }

        if ($_SESSION['user'] == -1) {
            $logger->trace('is admin session');
            return false;
        } else {
            $logger->debug('no admin session');
            return true;
        }
    }
}
