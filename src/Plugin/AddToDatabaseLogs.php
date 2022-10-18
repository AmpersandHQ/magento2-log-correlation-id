<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\Plugin;

use Ampersand\LogCorrelationId\Service\CorrelationIdentifier;

class AddToDatabaseLogs
{
    /**
     * @var CorrelationIdentifier
     */
    private CorrelationIdentifier $correlationIdentifier;

    /**
     * Constructor
     *
     * @param CorrelationIdentifier $correlationIdentifier
     */
    public function __construct(CorrelationIdentifier $correlationIdentifier)
    {
        $this->correlationIdentifier = $correlationIdentifier;
    }

    /**
     * Append the log correlation ID to the database log
     *
     * @param $subject
     * @param $string
     * @return string
     */
    public function beforeLog($subject, $string)
    {
        $logCorrelationIdString =
            $this->correlationIdentifier->getIdentifierKey() .
            '=' .
            $this->correlationIdentifier->getIdentifierValue() .
            PHP_EOL;

        return $logCorrelationIdString . $string;
    }
}
