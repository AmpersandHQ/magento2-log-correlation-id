<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\Plugin;

use Ampersand\LogCorrelationId\HttpResponse\HeaderProvider\LogCorrelationIdHeader as Header;
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
        if ($subject->getHeader(Header::X_LOG_CORRELATION_ID)) {
            return null;
        }
        $subject->setHeader(Header::X_LOG_CORRELATION_ID, $this->retriever->getIdentifierValue(), true);
        return null;
    }
}
