<?php

namespace Tiimber\Cli\Generate;

use Exception;

use Tiimber\Cli\PathResolver;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ControllerCommand extends Command
{
  protected function configure()
  {
    $this
      ->setName('generate:controller')
      ->setDescription('Tiimber controller generator')
      ->addArgument(
        'name',
        InputArgument::REQUIRED,
        'Name of the controller'
      )
      ->addOption(
        'project',
        'p',
        InputOption::VALUE_REQUIRED,
        'Name of the application where you need create controller'
      )
    ;
  }

  private function createController($name, $project)
  {
    $controllerDir = (new PathResolver())->getAppDir() . $project . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR;

    $controllerContent = <<<'EOS'
<?php

namespace {{project}}\Controllers;

use Tiimber\AbstractController;

class {{controller}}Controller extends AbstractController
{

}

EOS;
    $controllerContent = str_replace(['{{project}}', '{{controller}}'], [$project, ucfirst($name)], $controllerContent);
    file_put_contents($controllerDir . ucfirst($name) . 'Controller.php', $controllerContent);
  }

  private function createTemplateFolder($name)
  {
    $templateDir = (new PathResolver())->getTplDir() . $name;
    mkdir($templateDir);
    touch($templateDir . DIRECTORY_SEPARATOR . '.gitkeep');
  }

  private function createConfigFile($name)
  {
    $configPath = (new PathResolver())->getRouteDir() . $name . '.json';
    file_put_contents($configPath, '{}');
  }

  private function declareController($controller, $project)
  {
    $filePath = (new PathResolver())->getConfDir() . 'controllers.json';
    $content = json_decode(file_get_contents($filePath));
    if (isset($content->$controller)) {
      throw new Exception('Controller already declared');
    }
    $content->$controller = $project . '\\Controllers\\' . ucfirst($controller);
    file_put_contents($filePath, json_encode($content, JSON_OPTIONS));
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $appDir = (new PathResolver())->getAppDir();

    if (!is_null($input->getOption('project')) && !is_dir($appDir . ucfirst($input->getOption('project')))) {
      return $output->writeln('<fg=red>Run command "tiimber generate:project ' . $input->getOption('project') . '" first.</>');
    }

    if (
      !file_exists($appDir) ||
      count(scandir($appDir)) == 2
    ) {
      return $output->writeln('<fg=red>Run command "tiimber generate:project <name>" first.</>');
    }
    $project = $input->getOption('project');
    if (is_null($project)) {
      $project = (new PathResolver)->resolveProjectName();
    }

    $output->write('<fg=yellow>Controller ' . $input->getArgument('name') . '</>');
    $this->declareController($input->getArgument('name'), $project);
    $this->createConfigFile($input->getArgument('name'));
    $this->createController($input->getArgument('name'), $project);
    $this->createTemplateFolder($input->getArgument('name'));
    $output->writeln('<fg=green> created.</>');
  }
}