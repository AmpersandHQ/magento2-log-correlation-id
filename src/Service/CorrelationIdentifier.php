<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\Service;

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
                $identifier = substr($idFromHeader, 0, 64);
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
}
