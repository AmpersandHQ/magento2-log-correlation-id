<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\Test\Integration;

use Ampersand\LogCorrelationId\Service\RetrieveCorrelationIdentifier;
use Magento\Framework\Logger\Monolog;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class StandardLoggerTest extends TestCase
{
    /** @var Monolog */
    private $logger;
    /** @var string */
    private $logDir;

    protected function setUp(): void
    {
        /** @var \Magento\TestFramework\ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();
        $this->logger = $objectManager->get(MonoLog::class);
        $this->logDir = Bootstrap::getInstance()->getAppTempDir() . '/var/log/';
    }

    public function testLog()
    {
        $logMessage = uniqid('some_log_entry_');
        $this->logger->debug($logMessage, ['some' => 'context']);
        $lineFromLog = $this->getLineFromLog('debug.log', $logMessage);

        $identifier = Bootstrap::getObjectManager()->get(RetrieveCorrelationIdentifier::class)->getIdentifierValue();
        $this->assertStringContainsString($identifier, $lineFromLog);
    }

    public function testLogSystem()
    {
        $logMessage = uniqid('some_log_entry_');
        $this->logger->info($logMessage, ['some' => 'context']);
        $lineFromLog = $this->getLineFromLog('system.log', $logMessage);

        $identifier = Bootstrap::getObjectManager()->get(RetrieveCorrelationIdentifier::class)->getIdentifierValue();
        $this->assertStringContainsString($identifier, $lineFromLog);
    }

    private function getLineFromLog($logfile, $logMessage)
    {
        $logFile = $this->logDir . $logfile;
        $this->assertFileExists($logFile, ' log file does not exist');
        $contents = \file_get_contents($logFile);
        $this->assertStringContainsString($logMessage, $contents, 'Log file does not contain message');

        // Get the line containing this unique message
        $contents = array_filter(
            explode(PHP_EOL, $contents),
            function ($line) use ($logMessage) {
                return strpos($line, $logMessage) !== false;
            }
        );

        $this->assertCount(1, $contents, 'We should only a unique entry with this log message');
        $line = array_pop($contents);
        return $line;
    }
}
