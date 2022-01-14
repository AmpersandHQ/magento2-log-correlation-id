<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\Test\Unit\Service;

use Ampersand\LogCorrelationId\Service\RetrieveCorrelationIdentifier;
use Magento\Framework\App\Request\Http as HttpRequest;
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

    public function testGetIdentifierWithoutInit()
    {
        $service = $this->createService();
        $this->assertEquals('correlation_id_error', $service->getIdentifierValue());
    }

    public function testGetIdentifier()
    {
        $httpRequest = $this->createMock(HttpRequest::class);
        $httpRequest->expects($this->any())
            ->method('getHeader')
            ->willReturn(false);

        $service = $this->createService();
        $service->init($httpRequest);
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

    public function testGetIdentifierFromHeader()
    {
        $httpRequest = $this->createMock(HttpRequest::class);
        $httpRequest->expects($this->any())
            ->method('getHeader')
            ->with('x-header-name-here')
            ->willReturn('abc123def456');

        $service = $this->createService('amp_correlation_id', 'x-header-name-here');
        $service->init($httpRequest);

        $val = $service->getIdentifierValue();
        $this->assertIsString($val, 'Identifier should be a string');
        $this->assertEquals('abc123def456', $val);
    }

    /**
     * @param string $key
     * @param string $header
     * @return RetrieveCorrelationIdentifier
     */
    private function createService(
        string $key = 'amp_correlation_id',
        string $header = ''
    ): RetrieveCorrelationIdentifier {
        return new RetrieveCorrelationIdentifier($key, $header);
    }
}
