<?php

use Psr\Log\LoggerInterface as Logger;
use Noodlehaus\Config;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;

class InputValidationService
{
    private $logger;
    private $config;

    public function __construct(Logger $logger, Config $config)
    {
        $this->logger = $logger;
        $this->config = $config;
    }

    public function invalidEditSellerLimit($inputArray)
    {
        $logger->trace('seller limit close request input array', $inputArray);

        $idValidator = v::key('id', v::intVal()->positive());
        $newLimitValidator = v::key('new_limit', v::intVal()->positive());

        $validator = v::allOf($idValidator, $newLimitValidator);

        try {
            $validator->assert($inputArray);
            $this->logger->debug('valid seller limit close request');
            return false;
        } catch (NestedValidationException $exception) {
            $this->logger->debug('invalid seller limit close request', $exception->getMessages());
            return true;
        }
    }
}
