<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\Test\Integration;

use Magento\Framework\Module\ModuleListInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class ModuleIsInstalledTest extends TestCase
{
    public function testModuleIsInstalled()
    {
        $moduleNames = Bootstrap::getObjectManager()->get(ModuleListInterface::class)->getNames();
        $this->assertContains('Ampersand_LogCorrelationId', $moduleNames);
    }
}
