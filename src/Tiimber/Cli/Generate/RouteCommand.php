<?php

namespace Tiimber\Cli\Generate;

use stdClass;

use Tiimber\Cli\PathResolver;
use Tiimber\Cli\Exception;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\ChoiceQuestion;

class RouteCommand extends Command
{
  protected function configure()
  {
    $this
      ->setName('generate:route')
      ->setDescription('Tiimber route generator')
      ->addArgument(
        'controller',
        InputArgument::REQUIRED,
        'Controller Name'
      )
      ->addArgument(
        'action',
        InputArgument::REQUIRED,
        'Action name'
      )
      ->addOption(
        'project',
        'p',
        InputOption::VALUE_REQUIRED,
        'Name of the application where you need create controller'
      )
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    try {
      $this->testRequirement($input);
    } catch (Exception $e) {
       return $output->writeln('<fg=red>' . $e->getMessage() . '</>');
    }

    $helper = $this->getHelper('question');
    $route = [
      'url' => $this->askForUrl($helper, $input, $output),
      'controller' => $input->getArgument('controller'),
      'action' => $input->getArgument('action'),
      'layout' =>  $this->askForLayout($helper, $input, $output)
    ];

    $security = $this->askForSecurity($helper, $input, $output);
    if ($security) {
      $route['security'] = $security;
    }

    $this->writeRoute($input->getArgument('controller'), $input->getArgument('action'), $route);
    $this->createTemplate($input->getArgument('controller'), $input->getArgument('action'));
  }

  private function testRequirement(InputInterface $input)
  {
    $appDir = (new PathResolver())->getAppDir();

    if (
      !is_null($input->getOption('project')) &&
      !is_dir($appDir . ucfirst($input->getOption('project')))
    ) {
      throw new Exception('Run command "tiimber generate:project ' . $input->getOption('project') . '" first.');
    }

    if (
      !file_exists($appDir) ||
      count(scandir($appDir)) == 2
    ) {
      throw new Exception('Run command "tiimber generate:project <name>" first.');
    }
    $folder = $input->getOption('project') ?: (new PathResolver())->resolveProjectName();

    if (
      !file_exists((new PathResolver())->getAppDir() . $folder . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . ucfirst($input->getArgument('controller')) . 'Controller.php')
    ) {
      throw new Exception('Controller ' . $input->getArgument('controller') . ' not found.'."\n".'You may run command "tiimber generate:controller <name>" first.');
    }
  }

  private function askForUrl($helper, $input, $output)
  {
    $question = new Question('<fg=yellow>Route url: </>', '');
    $question->setValidator(function ($answer) {
      if ('/' !== substr($answer, 0, 1)) {
        throw new \RuntimeException('The route may start with "/"');
      }
      return $answer;
    });
    $question->setMaxAttempts(3);
    return $helper->ask($input, $output, $question);
  }

  private function askForLayout($helper, $input, $output)
  {
    $layouts = [];
    $layoutDir = (new PathResolver())->getLayoutDir();
    $files = array_diff(scandir($layoutDir), ['.', '..']);
    foreach ($files as $file) {
      if (is_file($layoutDir . $file)) {
        $layouts[] = basename($layoutDir . $file, '.phtml');
      }
    }
    $default = reset(array_keys($layouts, 'default'));

    $question = new ChoiceQuestion(
      '<fg=yellow>Select a layout [' . $default . ']: </>',
      $layouts,
      $default
    );

    return $helper->ask($input, $output, $question);
  }

  private function askForSecurity($helper, $input, $output)
  {
    $isSecureQuestion = new ConfirmationQuestion('<fg=yellow>is secure [n]: </>', false);
    if ($helper->ask($input, $output, $isSecureQuestion)) {
      $security = json_decode((new PathResolver())->getConfDir() . 'security.json');
      $security = array_keys((array)$security);
      if (count($security) != 0) {
        $security = new ChoiceQuestion(
          '<fg=yellow>Select a layout [0]: </>',
          $layouts,
          0
        );

        return $helper->ask($input, $output, $question);
      }
      $output->writeln('<fg=red>No security rules found.</>');
      $output->writeln('<fg=red>You may run </><fg=white;bg=black;options=bold>vendor/bin/tiimber generate:security <name></><fg=red> first.</>');
    }
    return false;
  }

  private function writeRoute($controller, $action, $route)
  {
    $routeDir = (new PathResolver())->getRouteDir();
    $content = json_decode($routeDir . $controller . '.json');
    if (is_null($content)) {
      $content = new stdClass();
    }

    if (isset($content->{$controller . '_' . $action})) {
      Exception('Route already exist.');
    }

    $content->{$controller . '_' . $action} = $route;

    file_put_contents($routeDir . $controller . '.json', json_encode($content, JSON_OPTIONS));
  }

  private function createTemplate($controller, $action)
  {
    touch((new PathResolver())->getTplDir() . $controller . DIRECTORY_SEPARATOR . $action . '.phtml');
  }
}