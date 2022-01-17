<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\Processor;

use Ampersand\LogCorrelationId\Service\CorrelationIdentifier;

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
     * @param array<mixed> $record
     * @return mixed[]
     */
    public function addCorrelationId(array $record): array
    {
        $key = $this->correlationIdentifier->getIdentifierKey();
        if (isset($record['context']) && is_array($record['context']) && !isset($record['context'][$key])) {
            $record['context'] = [$key => $this->correlationIdentifier->getIdentifierValue()] + $record['context'];
        }
        return $record;
    }
}
