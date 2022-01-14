<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\CacheDecorator;

use Ampersand\LogCorrelationId\Service\RetrieveCorrelationIdentifier;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Cache\Frontend\Decorator\Bare;
use Magento\Framework\Cache\FrontendInterface;

/**
 * Cache frontend decorator
 */
class CorrelationIdDecorator extends Bare
{
    /**
     * It seems strange to create a cache decorator and then not use it for decorating the cache, however in core
     * magento flow the cache decorator system is the bit responsible for instantiating the logger and the request
     * objects for the first time.
     *
     * Get the HTTP request in the same way that the core does
     * @see \Magento\Framework\Cache\Frontend\Decorator\Logger::__construct
     * @see \Magento\Framework\Cache\InvalidateLogger::__construct
     *
     * We need to get the correlation ID if it exists as early as possible in the PHP execution so this seems to be the
     * earliest place where the data exists and covers all magento instantiation journeys.
     *
     * @param FrontendInterface $frontend
     * @param HttpRequest $request
     * @param RetrieveCorrelationIdentifier $retriever
     */
    public function __construct(
        FrontendInterface $frontend,
        HttpRequest $request,
        RetrieveCorrelationIdentifier $retriever
    ) {
        parent::__construct($frontend);
        $retriever->init($request);

        /**
         * We want New Relic transaction info added as early as possible in the magento instantiation so that the
         * correlation id is present in the event of some error when booting up the application
         */
        if (extension_loaded('newrelic') && function_exists('newrelic_add_custom_parameter')) {
            newrelic_add_custom_parameter($retriever->getIdentifierKey(), $retriever->getIdentifierValue());
        }
    }
}
