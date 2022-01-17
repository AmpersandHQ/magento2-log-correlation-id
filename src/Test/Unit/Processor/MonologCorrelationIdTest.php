<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\Test\Unit\Processor;

use Ampersand\LogCorrelationId\Processor\MonologCorrelationId;
use Ampersand\LogCorrelationId\Service\CorrelationIdentifier;
use PHPUnit\Framework\TestCase;

class MonologCorrelationIdTest extends TestCase
{
    /**
     * @var MonologCorrelationId
     */
    private MonologCorrelationId $processor;

    protected function setUp(): void
    {
        $correlationIdentifier = $this->createMock(CorrelationIdentifier::class);
        $correlationIdentifier->expects($this->any())
            ->method('getIdentifierKey')
            ->willReturn('correlation_id');
        $correlationIdentifier->expects($this->any())
            ->method('getIdentifierValue')
            ->willReturn('identifier_value_123');

        $this->processor = new MonologCorrelationId($correlationIdentifier);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testAddCorrelationIdToContext($record, $expectedRecord)
    {
        $this->assertEquals($expectedRecord, $this->processor->addCorrelationId($record));
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            'add to context successful' => [
                [
                    'message' => 'dummy message',
                    'context' => ['foo' => 'bar', 'baz' => 'qux']
                ],
                [
                    'message' => 'dummy message',
                    'context' => ['correlation_id' => 'identifier_value_123', 'foo' => 'bar', 'baz' => 'qux']
                ],
            ],
            'key already exists in context' => [
                [
                    'message' => 'dummy message',
                    'context' => ['correlation_id' => 'asdf123', 'foo' => 'bar', 'baz' => 'qux']
                ],
                [
                    'message' => 'dummy message',
                    'context' => ['correlation_id' => 'asdf123', 'foo' => 'bar', 'baz' => 'qux']
                ],
            ],
            'no context available, leave it alone' => [
                [
                    'message' => 'dummy message',
                    'foo' => 'bar'
                ],
                [
                    'message' => 'dummy message',
                    'foo' => 'bar'
                ],
            ],
            'context is incorrect type, leave it alone' => [
                [
                    'message' => 'dummy message',
                    'context' => 'asdf',
                    'foo' => 'bar'
                ],
                [
                    'message' => 'dummy message',
                    'context' => 'asdf',
                    'foo' => 'bar'
                ],
            ]
        ];
    }
}
