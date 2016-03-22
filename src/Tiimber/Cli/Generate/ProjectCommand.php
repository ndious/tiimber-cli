<?php

namespace Tiimber\Cli\Generate;

use stdClass;

use Tiimber\Cli\Application;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectCommand extends Command
{
  protected function configure()
  {
    $this
      ->setName('generate:project')
      ->setDescription('Tiimber project generation')
      ->addArgument(
        'name',
        InputArgument::REQUIRED,
        'Name of the project'
      )
    ;
  }

  private function createFolders($dir, $name)
  {
    $appDir = $dir . DIRECTORY_SEPARATOR . 'Application' . DIRECTORY_SEPARATOR . ucfirst($name);
    $resourceDir = $dir . DIRECTORY_SEPARATOR . 'Resources';

    mkdir($dir . DIRECTORY_SEPARATOR . 'Application');
    mkdir($appDir);

    mkdir($appDir . DIRECTORY_SEPARATOR . 'Controllers');
    mkdir($appDir . DIRECTORY_SEPARATOR . 'Models');
    mkdir($appDir . DIRECTORY_SEPARATOR . 'Tables');

    touch($appDir . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . '.gitkeep');
    touch($appDir . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR . '.gitkeep');
    touch($appDir . DIRECTORY_SEPARATOR . 'Tables' . DIRECTORY_SEPARATOR . '.gitkeep');

    mkdir($dir . DIRECTORY_SEPARATOR . 'Config');

    mkdir($resourceDir);
    mkdir($resourceDir . DIRECTORY_SEPARATOR . 'images');
    mkdir($resourceDir . DIRECTORY_SEPARATOR . 'javascript');
    mkdir($resourceDir . DIRECTORY_SEPARATOR . 'stylesheet');

    touch($resourceDir . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . '.gitkeep');
    touch($resourceDir . DIRECTORY_SEPARATOR . 'javascript' . DIRECTORY_SEPARATOR . '.gitkeep');
    touch($resourceDir . DIRECTORY_SEPARATOR . 'stylesheet' . DIRECTORY_SEPARATOR . '.gitkeep');

    mkdir($dir . DIRECTORY_SEPARATOR . 'Templates');
    touch($dir . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR . '.gitkeep');
  }

  private function createConfig($dir, $name)
  {
    $confiDir = $dir . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR;
    $database = [
      'host' => 'localhost',
      'dbname' => $name,
      'charset' => 'utf8',
      'login' => 'root',
      'password' => ''
    ];
    $helpers = [
      'url' => 'Tiimber\\Helpers\\UrlHelper'
    ];

    file_put_contents($confiDir . 'database.json', json_encode($database, JSON_OPTIONS));
    file_put_contents($confiDir . 'controllers.json', '{}');
    file_put_contents($confiDir . 'security.json', '{}');
    file_put_contents($confiDir . 'helpers.json', json_encode($helpers, JSON_OPTIONS));
    file_put_contents($confiDir . 'drivers.json', '{}');

    mkdir($confiDir . 'routes');
    touch($confiDir . 'routes' . DIRECTORY_SEPARATOR . '.gitkeep');
  }

  private function updateComposer($dir, $name)
  {
    $composerPath = $dir . DIRECTORY_SEPARATOR . 'composer.json';
    $composer = file_get_contents($composerPath);
    $composer = json_decode($composer);

    if (!isset($composer->autoload)) {
      $composer->autoload = new stdClass();
    }
    if (!isset($composer->autoload->{'psr-4'})) {
      $composer->autoload->{'psr-4'} = new stdClass();
    }
    if (!isset($composer->autoload->{'psr-4'}->{ucfirst($name) . '\\'})) {
      $composer->autoload->{'psr-4'}->{ucfirst($name) . '\\'} = 'Application/' . ucfirst($name);
    }
    file_put_contents($composerPath, json_encode($composer, JSON_OPTIONS));
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $dir = Application::getBaseDir();

    $this->createFolders($dir, $input->getArgument('name'));

    $this->createConfig($dir, $input->getArgument('name'));

    $this->updateComposer($dir, $input->getArgument('name'));

    $output->writeln('<fg=green>Project ' . $input->getArgument('name') . ' created.</>');
  }
}