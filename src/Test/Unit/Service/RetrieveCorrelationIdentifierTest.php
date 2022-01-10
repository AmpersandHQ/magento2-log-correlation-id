<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\Test\Unit\Service;

use Ampersand\LogCorrelationId\Service\RetrieveCorrelationIdentifier;
use PHPUnit\Framework\TestCase;

class RetrieveCorrelationIdentifierTest extends TestCase
{
    public function testGetKey()
    {
        $this->assertEquals('foobar', $this->createService('foobar')->getIdentifierKey());
    }

    public function testGetKeyDefault()
    {
        $this->assertEquals('amp_correlation_id', $this->createService()->getIdentifierKey());
    }

    public function testGetIdentifier()
    {
        $service = $this->createService();
        $val1 = $service->getIdentifierValue();
        $this->assertIsString($val1, 'Identifier should be a string');
        $this->assertStringContainsString(
            'cid-',
            $val1,
            'Identifier should contain cid-'
        );

        $this->assertEquals($val1, $service->getIdentifierValue(), 'Repeat calls give same value');
        $this->assertEquals($val1, $service->getIdentifierValue(), 'Repeat calls give same value');
        $this->assertEquals($val1, $service->getIdentifierValue(), 'Repeat calls give same value');

        $service2 = $this->createService();
        $this->assertEquals(
            $service->getIdentifierKey(),
            $service2->getIdentifierKey(),
            'Multiple instances of the identifier service singleton should have same key'
        );
        $this->assertNotEquals(
            $service->getIdentifierValue(),
            $service2->getIdentifierValue(),
            'Multiple instances of the identifier service singleton should have different values'
        );
    }

    /**
     * @param string $key
     * @return RetrieveCorrelationIdentifier
     */
    private function createService(string $key = 'amp_correlation_id'): RetrieveCorrelationIdentifier
    {
        return new RetrieveCorrelationIdentifier($key);
    }
}
