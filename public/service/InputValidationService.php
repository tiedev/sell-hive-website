<?php

use Psr\Log\LoggerInterface as Logger;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;

class InputValidationService
{
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function invalidEditSeller($inputArray)
    {
        $this->logger->debug('seller edit request input array', $inputArray);

        $tomorrow = new DateTime('tomorrow');

        $limitRequestValidator = v::key('limitRequest', v::intVal()->min(0));
        $limitValidator = v::key('limit', v::intVal()->min(0));
        $limitTillValidator = v::key('limitTill', v::optional(v::date()->min($tomorrow)));

        // TODO : require validation that limit is not lower than item count

        $validator = v::allOf($limitRequestValidator, $limitValidator, $limitTillValidator);

        try {
            $validator->assert($inputArray);
            $this->logger->debug('valid seller limit close request');
            return false;
        } catch (NestedValidationException $exception) {
            $this->logger->debug('invalid seller limit close request', $exception->getMessages());
            return $exception->getMessages();
        }
    }
}
