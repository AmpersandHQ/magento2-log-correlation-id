<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\Service;

use Ampersand\LogCorrelationId\HttpResponse\HeaderProvider\LogCorrelationIdHeader as Header;
use Ampersand\LogCorrelationId\Service\CorrelationIdentifier\Storage;
use Magento\Framework\App\Request\Http as HttpRequest;

/**
 * This class cannot have any dependencies that are not fully defined in app/etc/*di.xml
 */
class CorrelationIdentifier
{
    /** @var string */
    private string $headerInput;

    /**
     * Constructor
     *
     * @param string $identifierKey
     * @param string $headerInput
     */
    public function __construct(string $identifierKey, string $headerInput)
    {
        Storage::setKey($identifierKey);
        $this->headerInput = $headerInput;
        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        register_shutdown_function([$this, 'shutDownFunction']);
    }

    /**
     * Initialise with the correlation identifier from the request object
     *
     * If no header present in the request object we will generate a unique one
     *
     * The force param is only here for use during test cases
     *
     * @param HttpRequest $request
     * @param false|true $force
     *
     * @return void
     */
    public function init(HttpRequest $request, bool $force = false)
    {
        $identifier = uniqid('cid-');

        if (is_string($this->headerInput) && strlen($this->headerInput)) {
            $idFromHeader = $request->getHeader($this->headerInput);
            if (is_string($idFromHeader) && strlen($idFromHeader)) {
                $identifier = substr($idFromHeader, 0, 256);
            }
        }

        Storage::setValue($identifier, $force);
    }

    /**
     * Return this processes log correlation ID
     *
     * @return string
     */
    public function getIdentifierValue(): string
    {
        return Storage::getValue();
    }

    /**
     * Return the identifier key, customisable via di.xml
     *
     * @return string
     */
    public function getIdentifierKey(): string
    {
        return Storage::getKey();
    }

    /**
     * A shutdown function to ensure the correlation ID header is added for every type of erroring request
     *
     * This was added to catch "Allowed memory size of X bytes exhausted" type errors
     *
     * @see \Magento\Framework\Webapi\ErrorProcessor::registerShutdownFunction
     *
     * @return void
     */
    public function shutDownFunction()
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        if (headers_sent()) {
            return;
        }

        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        $headerAlreadyDefined = array_filter(headers_list(), function ($header) {
            return (stripos($header, Header::X_LOG_CORRELATION_ID) !== false);
        });

        if (!empty($headerAlreadyDefined)) {
            return;
        }

        // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
        header(Header::X_LOG_CORRELATION_ID . ': ' . $this->getIdentifierValue());
    }
}
