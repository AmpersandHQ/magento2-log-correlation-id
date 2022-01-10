<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\Processor;

use Ampersand\LogCorrelationId\Service\RetrieveCorrelationIdentifier;

class MonologCorrelationId
{
    /**
     * @var RetrieveCorrelationIdentifier
     */
    private RetrieveCorrelationIdentifier $retriever;

    /**
     * Constructor
     *
     * @param RetrieveCorrelationIdentifier $retriever
     */
    public function __construct(RetrieveCorrelationIdentifier $retriever)
    {
        $this->retriever = $retriever;
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
        $key = $this->retriever->getIdentifierKey();
        if (isset($record['context']) && is_array($record['context']) && !isset($record['context'][$key])) {
            $record['context'] = [$key => $this->retriever->getIdentifierValue()] + $record['context'];
        }
        return $record;
    }
}
