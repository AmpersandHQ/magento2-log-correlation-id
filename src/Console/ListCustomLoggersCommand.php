<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\Console;

use Magento\Framework\Console\Cli;
use Magento\Framework\Module\Dir as ModuleDir;
use Magento\Framework\Module\FullModuleList as ModuleList;
use Monolog\Logger as MonologLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCustomLoggersCommand extends Command
{
    /**
     * Name of input option
     */
    const INPUT_KEY_FILTER = 'filter';

    /** @var ModuleDir $dir */
    private $dir;
    /** @var ModuleList $list */
    private $list;

    /**
     * Constructor
     *
     * @param ModuleDir $dir
     * @param ModuleList $list
     */
    public function __construct(ModuleDir $dir, ModuleList $list)
    {
        $this->dir = $dir;
        $this->list = $list;
        parent::__construct();
    }

    /**
     * Configure the command
     *
     * @return void
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::INPUT_KEY_FILTER,
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Filter a logger from the output results'
            ),
        ];

        $this->setName('ampersand:log-correlation-id:list-custom-loggers');
        $this->setDescription('List custom monolog loggers defined in magento modules');
        $this->setDefinition($options);

        parent::configure();
    }

    /**
     * List all module classes that extend the monolog logger
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $returnCode = Cli::RETURN_SUCCESS;
        try {
            $output->writeln(str_pad('', 80, '-'));
            $output->writeln("Scanning for classes which extend " . MonologLogger::class);
            $output->writeln(str_pad('', 80, '-'));
            $output->writeln("- You need to run 'composer dump-autoload --optimize' for this command to work");
            $output->writeln("- Use this on your local environment to configure your di.xml");
            $output->writeln("- See vendor/ampersand/magento2-log-correlation-id/README.md");
            $output->writeln(str_pad('', 80, '-'));

            $extendsMonologLogger = [];

            $classMap = $this->getOptimisedAutoloadClassMap();

            foreach ($this->list->getNames() as $moduleName) {
                $moduleDirectory = $this->dir->getDir($moduleName) . DIRECTORY_SEPARATOR;

                // Get all the files from the classmap pertaining to this module
                $filesFromClassMap = array_filter(
                    $classMap,
                    function ($filepath) use ($moduleDirectory) {
                        return substr($filepath, 0, strlen($moduleDirectory)) === $moduleDirectory;
                    }
                );

                // From this list of files get the list of them that extend the Monolog Logger class
                $moduleClassesThatExtendMonologLogger = array_filter(
                    array_keys($filesFromClassMap),
                    function ($class) use ($output) {
                        try {
                            $result = is_subclass_of($class, MonologLogger::class);
                        } catch (\Throwable $throwable) {
                            $output->writeln(sprintf(
                                "<error>Failed to scan %s - %s</error>",
                                str_pad($class, 80, ' '),
                                $throwable->getMessage()
                            ));
                            $result = false;
                        }
                        return $result;
                    }
                );

                if (empty($moduleClassesThatExtendMonologLogger)) {
                    continue;
                }
                // phpcs:ignore Magento2.Performance.ForeachArrayMerge.ForeachArrayMerge
                $extendsMonologLogger = array_merge($extendsMonologLogger, $moduleClassesThatExtendMonologLogger);
            }

            /** @var string[] $filters */
            $filters = $input->getOption(self::INPUT_KEY_FILTER);
            $extendsMonologLogger = array_diff($extendsMonologLogger, $filters);

            if (!empty($filters) && !empty($extendsMonologLogger)) {
                /*
                 * We have supplied filters but still have results output, so we have not filtered everything
                 * Likely a new logger has been added that needs accounted for in di.xml
                 */
                $returnCode = Cli::RETURN_FAILURE;
            }

            sort($extendsMonologLogger);
            foreach ($extendsMonologLogger as $className) {
                $output->writeln($className);
            }

            $output->writeln(str_pad('', 80, '-'));
            $output->writeln('DONE');
        } catch (\Throwable $throwable) {
            $output->writeln("<error>" . $throwable->getMessage() . "</error>");
            return Cli::RETURN_FAILURE;
        }
        return $returnCode;
    }

    /**
     * This is the same autoloading mechanism as in vendor/magento/magento2-base/app/autoload.php
     *
     * However we cannot use the wrapper as we need direct access ot the composer autoloader data
     *
     * @phpcs:disable Magento2.Security.IncludeFile.FoundIncludeFile
     * @phpcs:disable Magento2.Functions.DiscouragedFunction.DiscouragedWithAlternative
     * @phpcs:disable Magento2.Exceptions.DirectThrow.FoundDirectThrow
     *
     * @return array<string, string>
     * @throws \Exception
     */
    protected function getOptimisedAutoloadClassMap(): array
    {
        // Get the vendor path
        // @phpstan-ignore-next-line
        $vendorDir = include VENDOR_PATH;
        // @phpstan-ignore-next-line
        $vendorAutoload = BP . "/{$vendorDir}/autoload.php";
        if (!is_readable($vendorAutoload)) {
            throw new \Exception("Could not find vendor/autoload.php at " . $vendorAutoload);
        }

        // Get the optimised autoload classmap
        $classMap = (include $vendorAutoload)->getClassMap();

        // Filter out files without a concrete class
        foreach (array_filter($classMap) as $className => $filePath) {
            try {
                if (strpos($filePath, '/TestFramework/') !== false) {
                    unset($classMap[$className]); // Filter out any test framework results
                    continue;
                }
                if (strpos($className, '\\TestFramework\\') !== false) {
                    unset($classMap[$className]); // Filter out any test framework results
                    continue;
                }
                if (strpos($filePath, '/Test/') !== false) {
                    unset($classMap[$className]); // Filter out any test framework results
                    continue;
                }
                if (strpos($className, '\\Test\\') !== false) {
                    unset($classMap[$className]); // Filter out any test framework results
                    continue;
                }
                $realPath = realpath($filePath);
                if (!$realPath) {
                    unset($classMap[$className]); // Could not work out realpath
                    continue;
                }
            } catch (\Throwable $throwable) {
                unset($classMap[$className]); // Could not work out realpath
                continue;
            }
            $classMap[$className] = $realPath;
        }

        return array_filter($classMap);
    }
}
