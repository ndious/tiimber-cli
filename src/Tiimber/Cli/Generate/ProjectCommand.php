<?php

namespace Tiimber\Cli\Generate;

use stdClass;

use Tiimber\Cli\PathResolver;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ProjectCommand extends Command
{
  protected function configure()
  {
    $this
      ->setName('generate:project')
      ->setDescription('Tiimber project generator')
      ->addArgument(
        'name',
        InputArgument::REQUIRED,
        'Name of the project'
      )
    ;
  }

  private function createFolders($name)
  {
    $appDir = (new PathResolver())->getAppDir() . ucfirst($name) . DIRECTORY_SEPARATOR;
    $resourceDir = (new PathResolver())->getResourceDir();

    mkdir((new PathResolver())->getAppDir());

    mkdir($appDir);
    mkdir($appDir . 'Controllers');
    mkdir($appDir . 'Models');
    mkdir($appDir . 'Tables');

    touch($appDir . 'Controllers' . DIRECTORY_SEPARATOR . '.gitkeep');
    touch($appDir . 'Models' . DIRECTORY_SEPARATOR . '.gitkeep');
    touch($appDir . 'Tables' . DIRECTORY_SEPARATOR . '.gitkeep');

    mkdir((new PathResolver())->getConfDir());

    mkdir($resourceDir);
    mkdir($resourceDir . 'images');
    mkdir($resourceDir . 'javascript');
    mkdir($resourceDir . 'stylesheet');

    touch($resourceDir . 'images' . DIRECTORY_SEPARATOR . '.gitkeep');
    touch($resourceDir . 'javascript' . DIRECTORY_SEPARATOR . '.gitkeep');
    touch($resourceDir . 'stylesheet' . DIRECTORY_SEPARATOR . 'application.css');

    mkdir((new PathResolver())->getTplDir());
    mkdir((new PathResolver())->getLayoutDir());

    mkdir((new PathResolver())->getRootDir() . 'Log');
    touch((new PathResolver())->getRootDir() . 'Log' . DIRECTORY_SEPARATOR . 'log.txt');
  }

  private function createConfig($name)
  {
    $confiDir = (new PathResolver())->getConfDir();
    $database = [
      'host' => 'localhost',
      'dbname' => $name,
      'charset' => 'utf8',
      'login' => 'root',
      'password' => ''
    ];
    $helpers = [
      'url' => 'Tiimber\\Helpers\\UrlHelper',
      'stylesheet' => 'Tiimber\\Helpers\\StylesheetHelper',
      'javascript' => 'Tiimber\\Helpers\\JavascriptHelper'
    ];

    file_put_contents($confiDir . 'database.json', json_encode($database, JSON_OPTIONS));
    file_put_contents($confiDir . 'controllers.json', '{}');
    file_put_contents($confiDir . 'security.json', '{}');
    file_put_contents($confiDir . 'helpers.json', json_encode($helpers, JSON_OPTIONS));
    file_put_contents($confiDir . 'drivers.json', '{}');

    mkdir((new PathResolver())->getRouteDir());
    touch((new PathResolver())->getRouteDir() . '.gitkeep');
  }

  private function updateComposer($name)
  {
    $composerPath = (new PathResolver())->getRootDir() . 'composer.json';
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

  private function createDefaultLayout()
  {
    $content = <<<'EOS'
<html>
  <head>
    <title>Tiimber project</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" maximum-scale="1">
    <meta charset="UTF-8">
    <?= $this->stylesheet('application.css') ?>
  </head>
  <boby>
    <?= $content ?>
  </body>
</html>
EOS;
    file_put_contents((new PathResolver())->getLayoutDir() . 'default.phtml', $content);
  }

  private function createIndex()
  {
    $content = <<<'EOS'
<?php

require __DIR__.'/vendor/autoload.php';

$application = new \Tiimber\Application();

$application->setBaseDir(__DIR__);
$application->start();

EOS;
    file_put_contents((new PathResolver())->getRootDir() . 'index.php', $content);
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $output->write('<fg=yellow>Project structure</>');
    $this->createFolders($input->getArgument('name'));
    $this->createDefaultLayout();
    $output->writeln('<fg=green> created.</>');

    $output->write('<fg=yellow>Project config</>');
    $this->createConfig($input->getArgument('name'));
    $output->writeln('<fg=green> created.</>');

    $output->write('<fg=yellow>Project index</>');
    $this->createIndex();
    $output->writeln('<fg=green> created.</>');

    $helper = $this->getHelper('question');
    $question = new ConfirmationQuestion('<fg=yellow>Add project namespace to composer.json [y]: </>', true);

    $updateComposer = $helper->ask($input, $output, $question);
    if ($updateComposer) {
      $output->write('<fg=yellow>composer.json</>');
      $this->updateComposer($input->getArgument('name'));
      $output->writeln('<fg=green> updated.</>');
    }


    $output->writeln('<fg=green>Project ' . $input->getArgument('name') . ' successfully generated.</>');
    $output->writeln('');
    $output->writeln('<fg=yellow>Whats next:</>');
    if ($updateComposer) {
      $output->writeln('<fg=yellow>  - Run </><fg=white;bg=black;options=bold>composer dump-autoload</><fg=yellow> to update autoloader.</>');
    }
    $output->writeln('<fg=yellow>  - Run </><fg=white;bg=black;options=bold>vendor/bin/tiimber generate:controller <name></><fg=yellow> to generate your first controller.</>');
    $output->writeln('');
  }
}