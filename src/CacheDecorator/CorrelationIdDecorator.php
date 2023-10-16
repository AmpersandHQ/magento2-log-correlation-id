<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\CacheDecorator;

use Ampersand\LogCorrelationId\Service\CorrelationIdentifier;
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
     *
     * @see \Magento\Framework\Cache\InvalidateLogger::__construct
     *
     * We need to get the correlation ID if it exists as early as possible in the PHP execution so this seems to be the
     * earliest place where the data exists and covers all magento instantiation journeys.
     *
     * @see \Magento\Framework\Cache\Frontend\Decorator\Logger::__construct
     *
     * @param FrontendInterface $frontend
     * @param HttpRequest $request
     * @param CorrelationIdentifier $correlationIdentifier
     */
    public function __construct(
        FrontendInterface $frontend,
        HttpRequest $request,
        CorrelationIdentifier $correlationIdentifier
    ) {
        parent::__construct($frontend);
        $correlationIdentifier->init($request);

        /**
         * We want New Relic transaction info added as early as possible in the magento instantiation so that the
         * correlation id is present in the event of some error when booting up the application
         */
        if (extension_loaded('newrelic') && function_exists('newrelic_add_custom_parameter')) {
            newrelic_add_custom_parameter(
                $correlationIdentifier->getIdentifierKey(),
                $correlationIdentifier->getIdentifierValue()
            );
        }

        /*
         * The support for cli_get_process_title and cli_set_process_title is not great everywhere, so if this fails
         * just do it quietly, otherwise it will break the entire application and log a lot of problems
         */
        // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
        @$this->setCliProcessTitle($correlationIdentifier, $request);
    }

    /**
     * Set the CLI process title, where possible depending on OS
     *
     * @param CorrelationIdentifier $correlationIdentifier
     * @param HttpRequest $request
     * @link https://github.com/php/php-src/issues/11246
     * @return void
     */
    private function setCliProcessTitle(
        CorrelationIdentifier $correlationIdentifier,
        HttpRequest $request
    ) {
        try {
            if (php_sapi_name() !== 'cli') {
                return;
            }
            $processTitle = cli_get_process_title();
            if (!is_string($processTitle)) {
                return;
            }
            if (!strlen($processTitle)) {
                /** @var array<string> $argv */
                $argv = $request->getServer('argv', []);
                /**
                 * @see https://github.com/magento/magento2/blob/2b3ad2eeb6903643b678ceeb7d1ea7361035dee7/app/code/Magento/Cron/Observer/ProcessCronQueueObserver.php#L973C43-L973C62
                 */
                $processTitle = PHP_BINARY . ' ' . implode(' ', $argv);
            }
            if (stripos($processTitle, $correlationIdentifier->getIdentifierValue()) !== false) {
                return;
            }
            cli_set_process_title($processTitle . ' (' . $correlationIdentifier->getIdentifierValue() . ') ');
            // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
        } catch (\Throwable $throwable) {
        }
    }
}
