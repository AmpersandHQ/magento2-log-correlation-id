<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\Service;

use Magento\Framework\App\Request\Http as HttpRequest;

/**
 * This class cannot have any dependencies that are not fully defined in app/etc/*di.xml
 */
class RetrieveCorrelationIdentifier
{
    /** @var string */
    private string $correlationIdKey;
    /** @var string */
    private string $headerInput;
    /** @var string */
    private string $correlationIdValue;

    /**
     * Constructor
     *
     * @param string $identifierKey
     * @param string $headerInput
     */
    public function __construct(string $identifierKey = 'amp_correlation_id', string $headerInput = '')
    {
        $this->correlationIdKey = $identifierKey;
        $this->headerInput = $headerInput;
    }

    /**
     * Initialiase with the correlation identifier from the request object
     *
     * If no header present in the request object we will generate a unique one
     *
     * @param HttpRequest $request
     * @return void
     */
    public function init(HttpRequest $request)
    {
        if (isset($this->correlationIdValue)) {
            return;
        }

        $identifier =  $this->correlationIdValue = uniqid('cid-');

        if (is_string($this->headerInput) && strlen($this->headerInput)) {
            $idFromHeader = $request->getHeader($this->headerInput);
            if (is_string($idFromHeader) && strlen($idFromHeader)) {
                $identifier = substr($idFromHeader, 0, 64);
            }
        }

        $this->correlationIdValue = $identifier;
    }

    /**
     * Return this processes log correlation ID, we should only ever use this class as a singleton to ensure consistency
     *
     * @return string
     */
    public function getIdentifierValue(): string
    {
        if (!isset($this->correlationIdValue)) {
            /**
             * This process has been generated in a way that the magento cache system wasn't triggered
             *
             * It is most likely you are running magento in developer mode, otherwise this needs reviewed
             *
             * @see \Magento\Framework\App\ObjectManagerFactory::class
             */
            return 'correlation_id_error';
        }
        return $this->correlationIdValue;
    }

    /**
     * Return the identifier key, customisable via di.xml
     *
     * @return string
     */
    public function getIdentifierKey(): string
    {
        return $this->correlationIdKey;
    }
}
