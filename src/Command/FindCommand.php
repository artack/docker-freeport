<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\String\UnicodeString;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

class FindCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('find')
            ->setDescription('Find a free port to use as a docker db port')
            ->addArgument('dir', InputArgument::OPTIONAL, 'If set, the task will search inside the given directory.')
            ->addOption('start', 's', InputOption::VALUE_OPTIONAL, 'If set, the port is used as a starting point.', '3306')
            ->addOption('depth', null, InputOption::VALUE_OPTIONAL, 'Directory depth to search for files', '<=2')
            ->addOption('services', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'key services', ['db'])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Finding a free PORT to use as docker db port');

        $dir = $input->getArgument('dir');
        if (!$dir) {
            $dir = getcwd();
        }

        $dir = realpath($dir);

        if (!$dir || !is_dir($dir)) {
            $io->error(sprintf('Given directory [%s] not found.', $input->getOption('dir')));

            return Command::FAILURE;
        }

        $depth = $input->getOption('depth');

        $finder = new Finder();
        $finder
            ->files()
            ->name('docker-compose.yml')
            ->name('docker-compose.override.yml')
            ->in($dir)
            ->depth($depth)
            ->exclude([
                'vendor',
                'vagrant-php',
                '.vagrant',
                'app',
                'intranet',
                'intranet-old',
                'frv',
                'offertrechner',
                'translations',
                'deployment',
                'assets',
                'var',
                'src',
                'config',
                'files',
                '.idea',
                'node_modules',
                'templates',
                'web',
                'public',
                'frontend',
                'system',
                'plugins',
                'check',
                'composer',
                'cache',
                'ansible',
                'provisioning',
                'engine',
                'themes',
                'media',
                'snippets',
                'test',
                'tests',
                'intercms',
                'resources',
                'isotope',
                'jira-attachments',
            ])
        ;

        if (!$finder->count()) {
            $io->warning('No files found to process');

            return Command::FAILURE;
        }

        $yaml = new Parser();

        $start = $input->getOption('start');
        if (!is_numeric($start) || $start < 3306) {
            $io->error(sprintf('Given PORT [%s] needs to be a number >= 3306.', $start));

            return Command::FAILURE;
        }

        $services = $input->getOption('services');

        $ports = [];
        $filesPerPort = [];

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            try {
                $value = $yaml->parseFile($file->getPathname());
            } catch (ParseException $e) {
                $io->warning(sprintf("YAML Parse error: %s\nFile [%s] on line [%d]", $e->getMessage(), $file->getPathname(), $e->getParsedLine()));
            }

            foreach ($services as $service) {
                if (isset($value['services'][$service]['ports'])) {
                    $servicePorts = $value['services'][$service]['ports'];

                    if (!\is_array($servicePorts)) {
                        $servicePorts = [$servicePorts];
                    }

                    foreach ($servicePorts as $servicePort) {
                        $portString = new UnicodeString($servicePort);
                        if ($portString->containsAny(':')) {
                            $portString = $portString->before(':');
                        }
                        $port = (int) $portString->toString();
                        $ports[] = $port;
                        $filesPerPort[$port][] = $file;
                    }
                }
            }
        }

        $duplicatedPorts = array_filter(array_count_values($ports), static function (int $port) {
            return $port > 1;
        });

        if (\count($duplicatedPorts) > 0) {
            foreach ($duplicatedPorts as $duplicatedPort => $times) {
                $io->warning(sprintf('port %d is used %d times!', $duplicatedPort, $times));
                $io->listing($filesPerPort[$duplicatedPort]);
            }
        }

        sort($ports);

        $firstPort = $ports[0];
        $lastPort = $ports[\count($ports) - 1];
        $fullRangePorts = range($firstPort, $lastPort);

        $holes = array_values(array_diff($fullRangePorts, $ports));
        sort($holes);

        if (\count($holes) > 0) {
            $io->success(sprintf('Patch a hole with the free PORT: %d', $holes[0]));

            return Command::SUCCESS;
        }

        $io->success(sprintf('Next free PORT: %d', $lastPort + 1));

        return Command::SUCCESS;
    }
}
