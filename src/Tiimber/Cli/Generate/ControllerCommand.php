<?php

namespace Tiimber\Cli\Generate;

use Exception;

use Tiimber\Cli\Application;

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
      ->setDescription('Tiimber controller generation')
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

  private function createController($dir, $name, $project = null)
  {
    $controllerDir = $dir . $project . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR;

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

  private function createTemplateFolder($dir, $name)
  {
    $templateDir = $dir . DIRECTORY_SEPARATOR . 'Templates' . DIRECTORY_SEPARATOR . $name;
    mkdir($templateDir);
    touch($templateDir . DIRECTORY_SEPARATOR . '.gitkeep');
  }

  private function createConfigFile($dir, $name)
  {
    $configPath = $dir . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . $name . '.json';
    file_put_contents($configPath, '{}');
  }

  private function declareController($dir, $controller, $project)
  {
    $filePath = $dir .DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'controllers.json';
    $content = json_decode(file_get_contents($filePath));
    if (isset($content->$controller)) {
      throw new Exception('Controller already declared');
    }
    $content->$controller = $project . '\\Controllers\\' . ucfirst($controller);
    file_put_contents($filePath, json_encode($content, JSON_OPTIONS));
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $appDir = Application::getBaseDir() . DIRECTORY_SEPARATOR . 'Application' . DIRECTORY_SEPARATOR;

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
      $folders = scandir($appDir);
      foreach ($folders as $folder) {
        if (!in_array($folder, ['.', '..']) && is_dir($appDir . DIRECTORY_SEPARATOR . $folder)) {
          $project = $folder;
          break;
        }
      }
    }

    $output->write('<fg=yellow>Controller ' . $input->getArgument('name') . '</>');
    $this->declareController(Application::getBaseDir(), $input->getArgument('name'), $project);
    $this->createConfigFile(Application::getBaseDir(), $input->getArgument('name'));
    $this->createController($appDir, $input->getArgument('name'), $project);
    $this->createTemplateFolder(Application::getBaseDir(), $input->getArgument('name'));
    $output->writeln('<fg=green> created.</>');
  }
}