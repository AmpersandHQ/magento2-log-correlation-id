<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\Test\Unit\Service;

use Ampersand\LogCorrelationId\HttpResponse\HeaderProvider\LogCorrelationIdHeader as Header;
use Ampersand\LogCorrelationId\Service\CorrelationIdentifier;
use Magento\Framework\App\Request\Http as HttpRequest;
use PHPUnit\Framework\TestCase;

class CorrelationIdentifierTest extends TestCase
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
        $this->assertEquals('correlation_id_value_error', $service->getIdentifierValue());
    }

    public function testGetIdentifier()
    {
        $httpRequest = $this->createMock(HttpRequest::class);
        $httpRequest->expects($this->any())
            ->method('getHeader')
            ->willReturn(false);

        $service = $this->createService();
        $service->init($httpRequest, true);
        $val1 = $service->getIdentifierValue();
        $this->assertIsString($val1, 'Identifier should be a string');
        $this->assertStringContainsString(
            'cid-',
            $val1,
            'Identifier should contain cid-'
        );
        $this->assertEquals(24, strlen($val1), 'Identifier should be this length, ' . $val1);

        $service2 = $this->createService();
        $service2->init($httpRequest, true);
        $this->assertEquals(
            $service->getIdentifierKey(),
            $service2->getIdentifierKey()
        );
        $this->assertNotEquals(
            $val1,
            $service2->getIdentifierValue()
        );
    }

    /**
     */
    public function testIdentifierHeaderIsSetOnShutdown()
    {
        $httpRequest = $this->createMock(HttpRequest::class);
        $httpRequest->expects($this->any())
            ->method('getHeader')
            ->willReturn(false);

        $service = $this->createService();
        $service->init($httpRequest, true);

        // https://stackoverflow.com/a/39892373/4354325
        $headerIsNotSet = empty(array_filter(xdebug_get_headers(), function ($header) {
            return (stripos($header, Header::X_LOG_CORRELATION_ID) !== false);
        }));
        $this->assertTrue($headerIsNotSet, 'The correlation header should not yet be set');

        $service->shutDownFunction();

        $headerIsSet = !empty(array_filter(xdebug_get_headers(), function ($header) {
            return (stripos($header, Header::X_LOG_CORRELATION_ID) !== false);
        }));
        $this->assertTrue($headerIsSet, 'The correlation header should be set');
    }

    public function testGetIdentifierFromHeader()
    {
        $httpRequest = $this->createMock(HttpRequest::class);
        $httpRequest->expects($this->any())
            ->method('getHeader')
            ->with('x-header-name-here')
            ->willReturn('abc123def456');

        $service = $this->createService('amp_correlation_id', 'x-header-name-here');
        $service->init($httpRequest, true);

        $val = $service->getIdentifierValue();
        $this->assertIsString($val, 'Identifier should be a string');
        $this->assertEquals('abc123def456', $val);
    }

    /**
     * @param string $key
     * @param string $header
     * @return CorrelationIdentifier
     */
    private function createService(
        string $key = 'amp_correlation_id',
        string $header = ''
    ): CorrelationIdentifier {
        // Reset the key value storage for the purposes of the test
        CorrelationIdentifier\Storage::setKey('', true);
        CorrelationIdentifier\Storage::setValue('', true);
        return new CorrelationIdentifier($key, $header);
    }
}
