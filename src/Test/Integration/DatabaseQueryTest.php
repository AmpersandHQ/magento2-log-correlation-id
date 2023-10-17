<?php
declare(strict_types=1);

namespace Ampersand\LogCorrelationId\Test\Integration;

use Ampersand\LogCorrelationId\Service\CorrelationIdentifier;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Logger\Handler\System as SystemLogHandler;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DatabaseQueryTest extends TestCase
{
    /** @var LoggerInterface */
    private $logger;

    /** @var \Magento\Framework\DB\Adapter\AdapterInterface  */
    private $connection;

    /** @var ResourceConnection  */
    private $resourceConnection;

    /** @var string */
    private $logDir = '';

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\RuntimeException
     */
    protected function setUp(): void
    {
        /** @var \Magento\TestFramework\ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();

        $this->resourceConnection = Bootstrap::getObjectManager()->get(ResourceConnection::class);
        $this->connection = $this->resourceConnection->getConnection();
        $this->logger = $objectManager->get(LoggerInterface::class);
        $this->logger->info('ensure something is written for the dir to exist');

        foreach ($this->logger->getHandlers() as $handler) {
            if ($handler instanceof SystemLogHandler) {
                $this->logDir = dirname($handler->getUrl());
                break;
            }
        }
    }

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\RuntimeException
     * @throws \ReflectionException
     */
    protected function tearDown(): void
    {
        $this->setDatabaseLoggingAlias('disabled');
    }

    /**
     * @throws \ReflectionException
     */
    public function testDatabaseLogsContainCorrelationId()
    {
        if (!getenv('IS_CI_PIPELINE')) {
            $this->markTestSkipped('Skipping this test as it is not in the modules CI pipeline');
        }

        $this->setDatabaseLoggingAlias('file');

        $query = 'select ' . time();
        $this->resourceConnection->getConnection()->query($query);

        $identifier = Bootstrap::getObjectManager()->get(CorrelationIdentifier::class)->getIdentifierValue();

        $line = $this->getLineFromLog($query);
        $this->assertStringContainsString($query, $line);
        $this->assertStringContainsString(" /* '$identifier' */ ", $line);
        $this->setDatabaseLoggingAlias('disabled');
    }

    /**
     * @param $logMessage
     * @return mixed|null
     */
    private function getLineFromLog($logMessage)
    {
        $contents = $this->getLogFileContents('db.log');
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

    /**
     * @param $logfile
     * @return false|string
     */
    private function getLogFileContents($logfile)
    {
        $logFile = $this->getLogFilePath($logfile);
        if (is_file($logFile)) {
            $this->assertFileExists($logFile, ' log file does not exist');
            clearstatcache(true, $logFile);
            $contents = \file_get_contents($logFile);
            return $contents;
        }
        return "$logFile does not exist" . PHP_EOL;
    }

    /**
     * @param $logfile
     * @return string
     */
    private function getLogFilePath($logfile)
    {
        return rtrim($this->logDir, DIRECTORY_SEPARATOR) . '/../debug/' . $logfile;
    }

    /**
     * The database logger is so low level it would have to reboot the integration tests from scratch
     *
     * Work around it by toggling on the database logger using reflection
     *
     * @param string $alias
     * @throws \ReflectionException
     */
    private function setDatabaseLoggingAlias($alias)
    {
        $connectionReflection = new \ReflectionObject($this->connection);
        $loggerReflection = $connectionReflection->getProperty('logger');
        $loggerReflection->setAccessible(true);

        $databaseLogger = $loggerReflection->getValue($this->connection);
        $databaseLoggerReflection = new \ReflectionObject($databaseLogger);

        $loggerProperty = $aliasProperty = null;

        // Navigate parents classes to get appropriate properties
        // We have the ampersand module extending the core, but plugins can put a third layer of interception on this
        for ($i=0; $i<5; $i++) {
            $props = $databaseLoggerReflection->getProperties();
            foreach ($props as $prop) {
                if (!$aliasProperty && $prop->getName() === 'loggerAlias') {
                    $prop->setAccessible(true);
                    $aliasProperty = $prop;
                }
                if (!$loggerProperty && $prop->getName() === 'logger') {
                    $prop->setAccessible(true);
                    $loggerProperty = $prop;
                }
            }
            if (isset($loggerProperty, $aliasProperty)) {
                break;
            }
            $databaseLoggerReflection = $databaseLoggerReflection->getParentClass();
        }

        if (!isset($loggerProperty, $aliasProperty)) {
            $this->fail('Could not get reflection properties');
        }

        $loggerProperty->setValue($databaseLogger, null); // to ensure lazy load picks it up and regenerates
        $aliasProperty->setValue($databaseLogger, $alias);
    }
}
