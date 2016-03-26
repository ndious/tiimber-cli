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
        'name',
        InputArgument::REQUIRED,
        'Entity name'
      )
      ->addOption(
        'rest',
        'r',
        InputOption::VALUE_NONE,
        'Generate a complet CRUD for rest api'
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

  }

  private function generateController($model)
  {
    $content = <<<'EOS'
<?php

namespace {{project}}\Controllers;

use Tiimber\AbstractRestController;

use {{project}}\Tables\{{model}}Table as Table;

class {{model}}Controller extends AbstractRestController
{
  public function getTable()
  {
    return new Table();
  }
}

EOS;
  }

  private function generateRoutes($model)
  {
    $routes = [
      'api_' . $model . '_get_collection' => [
        'route' => 'get::/api/v1/' .  $model,
        'action' => 'apiGetCollection',
        'controller' => $model,
        'layout' => 'blank'
      ],
      'api_' . $model . '_get' => [
        'route' => 'get::/api/v1/' .  $model . '/{id}',
        'require' => [
          'id' => '\d+'
        ],
        'action' => 'apiGet',
        'controller' => $model,
        'layout' => 'blank'
      ],
      'api_' . $model . '_create' => [
        'route' => 'post::/api/v1/' .  $model,
        'action' => 'apiCreate',
        'controller' => $model,
        'layout' => 'blank'
      ],
      'api_' . $model . '_update' => [
        'route' => 'update::/api/v1/' .  $model . '/{id}',
        'require' => [
          'id' => '\d+'
        ],
        'action' => 'apiUpdate',
        'controller' => $model,
        'layout' => 'blank'
      ],
      'api_' . $model . '_delete' => [
        'route' => 'delete::/api/v1/' .  $model . '/{id}',
        'require' => [
          'id' => '\d+'
        ],
        'action' => 'apiDelete',
        'controller' => $model,
        'layout' => 'blank'
      ]
    ];
  }
}