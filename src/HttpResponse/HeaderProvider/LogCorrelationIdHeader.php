<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\HttpResponse\HeaderProvider;

use Ampersand\LogCorrelationId\Service\RetrieveCorrelationIdentifier;
use Magento\Framework\App\Response\HeaderProvider\AbstractHeaderProvider;

class LogCorrelationIdHeader extends AbstractHeaderProvider
{
    /**
     * @var string
     */
    protected $headerName = 'X-Log-Correlation-Id';

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
