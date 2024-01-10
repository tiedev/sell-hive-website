<?php

use Psr\Log\LoggerInterface as Logger;

class AuthService
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function isNoUser(): bool
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

    public function isNoAdmin(): bool
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
