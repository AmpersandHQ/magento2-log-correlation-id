<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\Test\Unit\Service\CorrelationIdentifier;

use Ampersand\LogCorrelationId\Service\CorrelationIdentifier\Storage;
use PHPUnit\Framework\TestCase;

class StorageTest extends TestCase
{
    protected function setUp(): void
    {
        //Rest the static vars
        Storage::setKey('', true);
        Storage::setValue('', true);
    }

    public function testDefaultErrorMessage()
    {
        $this->assertEquals('correlation_id_value_error', Storage::getValue());
        $this->assertEquals('correlation_id_key_error', Storage::getKey());
    }

    public function testInit()
    {
        Storage::setKey('some_key');
        Storage::setValue('some_value');
        $this->assertEquals('some_key', Storage::getKey());
        $this->assertEquals('some_value', Storage::getValue());

        Storage::setKey('some_other_key');
        Storage::setValue('some_other_value');
        $this->assertEquals('some_key', Storage::getKey());
        $this->assertEquals('some_value', Storage::getValue());

        Storage::setKey('some_other_key', true);
        Storage::setValue('some_other_value', true);
        $this->assertEquals('some_other_key', Storage::getKey());
        $this->assertEquals('some_other_value', Storage::getValue());
    }
}
