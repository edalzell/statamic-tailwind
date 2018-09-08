<?php

namespace Statamic\Addons\Tailwind\Commands;

use Statamic\API\File;
use Statamic\API\Path;
use Statamic\API\Config;
use Statamic\API\Helper;
use Statamic\Extend\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tailwind:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Installs Tailwind and gets it ready for use';

    /**
     * Path to the installed theme.
     *
     * @var string
     */
    private $themePath;

    public function __construct()
    {
        parent::__construct();

        $this->themePath = Path::assemble(
            site_path('themes'),
            Config::get('theming.theme')
        );
    }

    public function handle()
    {
        // check dependencies

        $this->copyFiles();

        $this->runCommands();
    }

    private function copyFiles()
    {
        $files = [
            [
                'from' => 'package.json',
                'to' => 'package.json',
            ],
            [
                'from' => ['js', 'webpack.mix.js'],
                'to' => 'webpack.mix.js',
                'update' => true
            ],
            [
                'from' => ['css', 'base.css'],
                'to' => ['css', 'base.css'],
            ],
            [
                'from' => ['js', 'base.js'],
                'to' => ['js', 'base.js'],
            ],
        ];

        collect($files)->each(
            function ($file, $key) {
                $this->putFile(
                    $file['from'],
                    $file['to'],
                    array_get($file, 'update', false)
                );
            }
        );
    }

    private function runCommands()
    {
        $commands = [
            [
                'wd' => $this->themePath,
                'cmd' => 'yarn'
            ],
            [
                'wd' => $this->themePath,
                'cmd' => 'yarn',
                'args' => [
                    'add',
                    'tailwindcss',
                    '--dev',
                ],
            ],
            [
                'wd' => $this->themePath,
                'cmd' => Path::assemble($this->themePath, 'node_modules', '.bin', 'tailwind'),
                'args' => [
                    'init',
                    'tailwind.js'
                ],
            ],
        ];

        collect($commands)->each(function ($command, $key) {
            $builder = new ProcessBuilder();
            $builder
                ->setWorkingDirectory($command['wd'])
                ->setPrefix($command['cmd'])
                ->setArguments(array_get($command, 'args', []));

            $process = $builder->getProcess();

            $process->run(function ($type, $buffer) {
                if (Process::ERR === $type) {
                    $this->error($buffer);
                } else {
                    $this->info($buffer);
                }
            });

            // executes after the command finishes
            if (!$process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput());
            }
        });
    }

    /**
     * Copy file
     *
     * @param array $sourcePathArr Relative to the addon dir
     * @param array $destSourcePathArr Relative to the theme dir
     * @return void
     */
    private function putFile($sourcePathArr, $destPathArr, $update = false)
    {
        $assetsPath = Path::assemble('site', 'addons', 'Tailwind', 'resources', 'assets');
        $relativeSrcPath = Path::assemble(Helper::ensureArray($sourcePathArr));
        $srcPath = Path::assemble($assetsPath, $relativeSrcPath);

        $relativeDestPath = Path::assemble(Helper::ensureArray($destPathArr));
        $destPath = Path::assemble($this->themePath, $relativeDestPath);

        if (!$update) {
            File::copy($srcPath, $destPath, true);
        } else {
            if (File::exists($destPath)) {
                File::delete($destPath);
            }
            File::put(
                $destPath,
                str_replace('my-theme', Config::get('theming.theme'), File::get($srcPath))
            );
        }
    }
}
