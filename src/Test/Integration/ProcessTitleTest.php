<?php
declare(strict_types=1);

namespace Ampersand\LogCorrelationId\Test\Integration;

use Ampersand\LogCorrelationId\Service\CorrelationIdentifier;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class ProcessTitleTest extends TestCase
{
    /**
     * @return void
     */
    public function testProcessTitleHasCorrelationId()
    {
        if (!getenv('IS_CI_PIPELINE')) {
            $this->markTestSkipped('Skipping this test as it is not in the modules CI pipeline');
        }

        cli_set_process_title('');
        $this->assertEquals('', cli_get_process_title(), 'The process title should start empty');

        /*
         * These tests need to reinialize the app so that the cache decorator can be redone from scratch
         */
        Bootstrap::getInstance()->reinitialize();

        $identifier = Bootstrap::getObjectManager()->get(CorrelationIdentifier::class)->getIdentifierValue();

        $this->assertStringContainsString(
            "($identifier)",
            cli_get_process_title(),
            'The cli process title did not contain the identifier'
        );
    }
}
