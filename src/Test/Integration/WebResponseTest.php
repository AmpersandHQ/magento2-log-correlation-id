<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\Test\Integration;

use Magento\Framework\App\ResponseInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class WebResponseTest extends TestCase
{
    protected function setUp(): void
    {
        /** @var \Magento\TestFramework\ObjectManager $objectManager */
        $objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @dataProvider dataProvider
     */
    public function testResponseContainsHeader($class)
    {
        /** @var ResponseInterface $response */
        $response = Bootstrap::getObjectManager()->create($class);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertFalse($response->getHeader('X-Log-Correlation-id'), 'Header should not exist yet');

        ob_start();
        $response->sendResponse();
        ob_end_clean();

        $this->assertNotFalse($response->getHeader('X-Log-Correlation-id'), 'Header is not present');
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
