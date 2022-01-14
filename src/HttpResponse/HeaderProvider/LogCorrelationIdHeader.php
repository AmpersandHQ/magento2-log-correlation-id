<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\HttpResponse\HeaderProvider;

use Ampersand\LogCorrelationId\Service\RetrieveCorrelationIdentifier;
use Magento\Framework\App\Response\HeaderProvider\AbstractHeaderProvider;

class LogCorrelationIdHeader extends AbstractHeaderProvider
{
    public const X_LOG_CORRELATION_ID = 'X-Log-Correlation-Id';

    /**
     * @var string
     */
    protected $headerName = self::X_LOG_CORRELATION_ID;

    /**
     * Constructor
     *
     * @param RetrieveCorrelationIdentifier $retriever
     */
    public function __construct(RetrieveCorrelationIdentifier $retriever)
    {
        $this->headerValue = $retriever->getIdentifierValue();
    }
}
