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

use Doctrine\Common\Inflector\Inflector;

class EntityCommand extends Command
{
  protected function configure()
  {
    $this
      ->setName('generate:entity')
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
    $model = $input->getArgument('name');
    $pluralized = Inflector::pluralize($model);
    $project = $input->getOption('project');
    if (is_null($project)) {
      $project = (new PathResolver)->resolveProjectName();
    }

    $this->generateModel($project, $model);
    $this->generateTable($project, $model, $pluralized);

    if ($input->getOption('rest')) {
      $this->generateController($project, $pluralized);
      $this->generateRoutes($model, $pluralized);
      $this->declareController($pluralized, $project);
    }
  }

  private function generateModel($project, $model)
  {
    $dir = (new PathResolver())->getAppDir() . $project . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR;

    $content = <<<'EOS'
<?php

namespace {{project}}\Models;

use Tiimber\AbstractModel;

class {{model}} extends AbstractModel
{
}

EOS;

    $content = str_replace(
      ['{{project}}', '{{model}}'],
      [$project, ucfirst($model)],
      $content
    );

    file_put_contents($dir . ucfirst($model) . '.php', $content);
  }

  private function generateTable($project, $model, $table)
  {
    $dir = (new PathResolver())->getAppDir() . $project . DIRECTORY_SEPARATOR . 'Tables' . DIRECTORY_SEPARATOR;
    $content = <<<'EOS'
<?php

namespace {{project}}\Tables;

use Tiimber\AbstractTable;

class {{Table}}Table extends AbstractTable
{
  const TABLE = '{{table}}';

  const ENTITY = '{{project}}\Models\{{model}}';
}

EOS;

    $content = str_replace(
      ['{{project}}', '{{table}}', '{{Table}}', '{{model}}'],
      [$project, $table, ucfirst($table), ucfirst($model)],
      $content
    );

    file_put_contents($dir . ucfirst($table) . 'Table.php', $content);
  }

  private function generateController($project, $table)
  {
    $content = <<<'EOS'
<?php

namespace {{project}}\Controllers;

use Tiimber\AbstractRestController;

use {{project}}\Tables\{{table}}Table as Table;

class {{table}}Controller extends AbstractRestController
{
  public function getTable()
  {
    return new Table();
  }
}

EOS;

    $content = str_replace(
      ['{{project}}', '{{table}}'],
      [$project, ucfirst($table)],
      $content
    );

    $dir = (new PathResolver())->getAppDir() . $project . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR;
    file_put_contents($dir . ucfirst($table) . 'Controller.php', $content);
  }

  private function generateRoutes($model, $controller)
  {
    $routes = [
      'api_' . $model . '_get_collection' => [
        'route' => 'get::/api/v1/' .  $model,
        'action' => 'apiGetCollection',
        'controller' => $controller,
        'layout' => 'blank'
      ],
      'api_' . $model . '_get' => [
        'route' => 'get::/api/v1/' .  $model . '/{id}',
        'require' => [
          'id' => '\d+'
        ],
        'action' => 'apiGet',
        'controller' => $controller,
        'layout' => 'blank'
      ],
      'api_' . $model . '_create' => [
        'route' => 'post::/api/v1/' .  $model,
        'action' => 'apiCreate',
        'controller' => $controller,
        'layout' => 'blank'
      ],
      'api_' . $model . '_update' => [
        'route' => 'update::/api/v1/' .  $model . '/{id}',
        'require' => [
          'id' => '\d+'
        ],
        'action' => 'apiUpdate',
        'controller' => $controller,
        'layout' => 'blank'
      ],
      'api_' . $model . '_delete' => [
        'route' => 'delete::/api/v1/' .  $model . '/{id}',
        'require' => [
          'id' => '\d+'
        ],
        'action' => 'apiDelete',
        'controller' => $controller,
        'layout' => 'blank'
      ]
    ];

    $filePath = (new PathResolver())->getRouteDir() . $controller . '.json';

    file_put_contents($filePath, json_encode($routes, JSON_OPTIONS));
  }

  private function declareController($controller, $project)
  {
    $filePath = (new PathResolver())->getConfDir() . 'controllers.json';
    $content = json_decode(file_get_contents($filePath));
    if (isset($content->$controller)) {
      throw new Exception('Controller already declared');
    }
    $content->$controller = $project . '\\Controllers\\' . ucfirst($controller) . 'Controller';
    file_put_contents($filePath, json_encode($content, JSON_OPTIONS));
  }
}