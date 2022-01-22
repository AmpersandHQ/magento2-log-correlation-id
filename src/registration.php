<?php
declare(strict_types=1);
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Ampersand_LogCorrelationId', __DIR__);

if (PHP_SAPI === 'cli') {
    \Magento\Framework\Console\CommandLocator::register(
        \Ampersand\LogCorrelationId\Console\CommandList::class
    );
}
