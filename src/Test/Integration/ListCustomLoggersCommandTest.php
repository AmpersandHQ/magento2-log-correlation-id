<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\Test\Integration;

use Ampersand\LogCorrelationId\Console\ListCustomLoggersCommand;
use Magento\Framework\Module\Dir as ModuleDir;
use Magento\Framework\Module\FullModuleList as ModuleList;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ListCustomLoggersCommandTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
    }

    public function testListCustomLoggersCommandInCi()
    {
        if (!getenv('IS_CI_PIPELINE')) {
            $this->markTestSkipped('Skipping this test as it is not in the modules CI pipeline');
        }

        $tester = new CommandTester($this->objectManager->create(ListCustomLoggersCommand::class));
        $tester->execute([]);

        /*
         * Our modules CI pipeline ensures we have composer dump-autoload -o so we should see the bundled loggers
         */
        $this->assertStringContainsString(
            'Dotdigitalgroup\Email\Logger\Logger',
            $tester->getDisplay()
        );
        $this->assertStringContainsString(
            'Klarna\Core\Logger\Logger',
            $tester->getDisplay()
        );
    }

    public function testListCustomLoggersCommand()
    {
        $command = $this->getMockBuilder(ListCustomLoggersCommand::class)
            ->setConstructorArgs(
                [
                    $this->objectManager->get(ModuleDir::class),
                    $this->objectManager->get(ModuleList::class)
                ]
            )
            ->setMethods(['getOptimisedAutoloadClassMap'])
            ->getMock();

        // Spoof in the framework logger which is guaranteed to be here, pretend its in the catalog module
        $classMap = [
            'Magento\Framework\Logger\Monolog'        => BP . '/vendor/magento/module-catalog/',
        ];

        $command->expects($this->any())
            ->method('getOptimisedAutoloadClassMap')
            ->willReturn($classMap);

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertStringContainsString(
            'Magento\Framework\Logger\Monolog',
            $tester->getDisplay()
        );
    }
}
