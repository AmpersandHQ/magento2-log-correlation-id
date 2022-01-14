<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\Test\Integration;

use Ampersand\LogCorrelationId\HttpResponse\HeaderProvider\LogCorrelationIdHeader as Header;
use Magento\Framework\App\ResponseInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class WebResponseTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testResponseContainsHeader($class)
    {
        /** @var ResponseInterface $response */
        $response = Bootstrap::getObjectManager()->create($class);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertFalse($response->getHeader(Header::X_LOG_CORRELATION_ID), 'Header should not exist yet');

        ob_start();
        $response->sendResponse();
        ob_end_clean();

        $this->assertNotFalse($response->getHeader(Header::X_LOG_CORRELATION_ID), 'Header is not present');
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
