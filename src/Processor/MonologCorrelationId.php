<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\Processor;

use Ampersand\LogCorrelationId\Service\CorrelationIdentifier;
use Monolog\LogRecord;

class MonologCorrelationId
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
     * Add the log correlation ID as a piece of context
     *
     * @see \Monolog\Logger::addRecord
     * @param LogRecord $record
     * @return mixed[]
     */
    public function addCorrelationId(LogRecord $record): LogRecord
    {
        $record->extra[$this->correlationIdentifier->getIdentifierKey()] = $this->correlationIdentifier->getIdentifierValue();
        return $record;
    }
}
