<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\Plugin;

use Ampersand\LogCorrelationId\Service\RetrieveCorrelationIdentifier;
use Magento\Framework\HTTP\PhpEnvironment\Response as HttpResponse;

class AddToWebApiResponse
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
     * When sending the response attach X-Log-Correlation-Id
     *
     * @param HttpResponse $subject
     * @return null
     */
    public function beforeSendResponse(HttpResponse $subject):? string
    {
        if ($subject->getHeader('X-Log-Correlation-Id')) {
            return null;
        }
        $subject->setHeader('X-Log-Correlation-Id', $this->retriever->getIdentifierValue(), true);
        return null;
    }
}
