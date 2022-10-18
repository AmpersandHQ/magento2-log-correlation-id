<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\Plugin;

use Ampersand\LogCorrelationId\Service\CorrelationIdentifier;
use Magento\Framework\DB\LoggerInterface;

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
     * @param LoggerInterface $subject
     * @param string $string
     * @return string
     */
    public function beforeLog(LoggerInterface $subject, $string)
    {
        $logCorrelationIdString =
            $this->correlationIdentifier->getIdentifierKey() .
            '=' .
            $this->correlationIdentifier->getIdentifierValue() .
            PHP_EOL;

        return $logCorrelationIdString . $string;
    }
}
