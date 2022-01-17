<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\Test\Integration;

use Ampersand\LogCorrelationId\HttpResponse\HeaderProvider\LogCorrelationIdHeader as Header;
use Ampersand\LogCorrelationId\Service\CorrelationIdentifier;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\ResponseInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class WebResponseTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testResponseContainsGeneratedHeader($class)
    {
        /** @var ResponseInterface $response */
        $response = Bootstrap::getObjectManager()->create($class);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertFalse($response->getHeader(Header::X_LOG_CORRELATION_ID), 'Header should not exist yet');

        ob_start();
        $response->sendResponse();
        ob_end_clean();

        $header = $response->getHeader(Header::X_LOG_CORRELATION_ID);
        $this->assertNotFalse($header, 'Header is not present');
        $this->assertStringNotContainsString('correlation_id_error', $header->toString());
        $this->assertStringContainsString('cid-', $header->toString());
    }

    /**
     * @dataProvider dataProvider
     */
    public function testResponseContainsPassedThroughHeader($class)
    {
        /*
         * These tests need to reinialize the app so that the cache decorator can be redone from scratch otherwise
         * state leaks from one test to the other
         */
        Bootstrap::getInstance()->reinitialize();

        /*
         * Get the HTTP request and add a fake header
         */
        /** @var \Magento\TestFramework\Request $request */
        $request = Bootstrap::getObjectManager()->get(HttpRequest::class);
        $identifier = uniqid('artichoke');
        $request->getHeaders()->addHeaders(['X-Test-Some-Trace-Header' => $identifier]);

        /*
         * Reinitialize this class with the header to look at, this is usually done when magento boots and the cache
         * decorators are created
         */
        /** @var CorrelationIdentifier $correlationIdentifier */
        $correlationIdentifier = Bootstrap::getObjectManager()->get(CorrelationIdentifier::class);
        $correlationIdentifier->__construct($correlationIdentifier->getIdentifierKey(), 'X-Test-Some-Trace-Header');
        $correlationIdentifier->init($request, true);

        /*
         * Create the response class, verify no headers exist yet
         */
        /** @var ResponseInterface $response */
        $response = Bootstrap::getObjectManager()->create($class);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertFalse($response->getHeader(Header::X_LOG_CORRELATION_ID), 'Header should not exist yet');

        /*
         * Send response
         */
        ob_start();
        $response->sendResponse();
        ob_end_clean();

        /*
         * See response contains header
         */
        $header = $response->getHeader(Header::X_LOG_CORRELATION_ID);
        $this->assertNotFalse($header, 'Header is not present');
        $this->assertStringNotContainsString('correlation_id_error', $header->toString());
        $this->assertEquals(Header::X_LOG_CORRELATION_ID . ': ' . $identifier, $header->toString());

        /*
         * Reinitialise so that it contains a generated identifier
         */
        $correlationIdentifier->__construct($correlationIdentifier->getIdentifierKey(), 'X-Not-A-Real-Test-Header');
        $correlationIdentifier->init($request, true);
    }

    public function dataProvider()
    {
        return [
            'Media storage' => [
                \Magento\MediaStorage\Model\File\Storage\Response::class
            ],
            'Standard (and GraphQL)' => [
                \Magento\Framework\App\Response\Http::class
            ],
            'WebApi' => [
                \Magento\Framework\Webapi\Response::class
            ],
            'WebApi - Rest' => [
                \Magento\Framework\Webapi\Rest\Response::class
            ],
        ];
    }
}
