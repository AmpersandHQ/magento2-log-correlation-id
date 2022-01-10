<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\Service;

class RetrieveCorrelationIdentifier
{
    /** @var string */
    private string $correlationIdKey;
    /** @var string */
    private string $correlationIdValue;

    /**
     * Constructor
     *
     * @param string $identifierKey
     */
    public function __construct(string $identifierKey)
    {
        $this->correlationIdKey = $identifierKey;
        $this->correlationIdValue = uniqid('cid-');

        /**
         * This is a bit of a hack
         *
         * We want New Relic transaction info added as early as possible in the magento instantiation so that the
         * correlation id is present in the event of some error when booting up the application
         *
         * Putting it here ensures that it is added as soon as is possible. Depending on how magento is instantiated
         * different bits of this module will hook in first.
         * (sometimes it's the web header, sometimes is the monolog processor)
         *
         * We cannot rely on plugins or observers as this is too late in the process
         *
         * By putting this new relic logic in here we can ensure that every time we try and set a correlation ID on
         * either a log entry or on a web request that we'll have a corresponding value in new relic.
         *
         * It is possible to boot the magento without triggering this code, for example
         *
         * use Magento\Framework\App\Bootstrap;
         * require __DIR__ . '/../app/bootstrap.php';
         * $bootstrap = Bootstrap::create(BP, $_SERVER);
         * $obj = $bootstrap->getObjectManager();
         *
         * However as soon as the logger is instantiated this code will trigger, if you have no logs then there's not
         * anything to correlate to in any case
         *
         * @author Luke Rodgers <lr@amp.co>
         */
        if (extension_loaded('newrelic') && function_exists('newrelic_add_custom_span_parameter')) {
            newrelic_add_custom_parameter($this->correlationIdKey, $this->correlationIdValue);
        }
    }

    /**
     * Return this processes log correlation ID, we should only ever use this class as a singleton to ensure consistency
     *
     * @return string
     */
    public function getIdentifierValue(): string
    {
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
