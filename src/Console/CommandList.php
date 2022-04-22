<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\Console;

use Magento\Framework\Console\CommandListInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Provides list of commands to be available for uninstalled application
 *
 * @see \Magento\Backend\Console\CommandList
 * @see \Magento\Deploy\Console\CommandList
 */
class CommandList implements CommandListInterface
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Gets list of command classes
     *
     * @return string[]
     */
    private function getCommandsClasses(): array
    {
        return [
            ListCustomLoggersCommand::class
        ];
    }

    /**
     * Gets list of command instances, can be used without installing the application
     *
     * @return array<\Symfony\Component\Console\Command\Command>
     */
    public function getCommands(): array
    {
        /** @var array<\Symfony\Component\Console\Command\Command> $commands */
        $commands = [];
        foreach ($this->getCommandsClasses() as $class) {
            if (class_exists($class)) {
                $commands[] = $this->objectManager->get($class);
            } else {
                throw new \RuntimeException('Class ' . $class . ' does not exist');
            }
        }
        return $commands;
    }
}
