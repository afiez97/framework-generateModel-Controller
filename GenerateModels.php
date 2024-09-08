<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class GenerateModels extends Command
{
    protected $signature = 'make:models {connection?}';
    protected $description = 'Generate Lumen models and controllers based on database tables and connections';

    public function handle()
    {
        $connections = config('database.connections');
        $selectedConnection = $this->argument('connection');

        $routes = [];

        if ($selectedConnection) {
            $this->generateModelsForConnection($selectedConnection, $routes);
        } else {
            foreach ($connections as $connectionName => $connectionConfig) {
                $this->generateModelsForConnection($connectionName, $routes);
            }
        }

        $this->updateRoutes($routes);
    }

    protected function generateModelsForConnection($connection, &$routes)
    {
        try {
            $this->info("Generating models and controllers for connection: $connection");

            $tables = DB::connection($connection)->select('SHOW TABLES');

            foreach ($tables as $table) {
                $tableName = array_values(get_object_vars($table))[0];

                // Use table name exactly as model and controller names
                $modelClassName = $tableName;
                $controllerClassName = $tableName . 'Controller';

                $modelFileName = $modelClassName . '.php';
                $controllerFileName = $controllerClassName . '.php';

                $fields = DB::connection($connection)->select('DESCRIBE ' . $tableName);

                $primaryKey = '';
                $timestamps = false;
                $fillableFields = [];

                foreach ($fields as $field) {
                    if ($field->Key === 'PRI') {
                        $primaryKey = $field->Field;
                    }

                    if (in_array($field->Field, ['created_at', 'updated_at'])) {
                        $timestamps = true;
                    } else {
                        $fillableFields[] = "'{$field->Field}'";
                    }
                }

                $fillable = implode(', ', $fillableFields);
                $timestampsOption = $timestamps ? 'true' : 'false';
                $primaryKeyOption = $primaryKey ? "protected \$primaryKey = '$primaryKey';" : '';

                $modelContent = <<<EOD
<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

class $modelClassName extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, HasFactory;

    protected \$table = '$tableName';
    $primaryKeyOption
    protected \$fillable = [$fillable];
    public \$timestamps = $timestampsOption;
    protected \$connection = '$connection';
}
EOD;

                $modelPath = base_path('app/Models/' . $modelFileName);
                File::ensureDirectoryExists(dirname($modelPath));
                file_put_contents($modelPath, $modelContent);
                $this->info("Model $modelClassName generated successfully for connection '$connection'.");

                $controllerContent = <<<EOD
<?php

namespace App\Http\Controllers;

use App\Models\\$modelClassName;
use Illuminate\Http\Request;

class $controllerClassName extends Controller
{
    public function __construct()
    {
        \$this->middleware('auth');
    }

    public function index()
    {
        \$obj = $modelClassName::all();
        return response()->json([
            'success' => true,
            'message' => 'Show Success!',
            'data' => \$obj
        ], \$obj ? 200 : 404);
    }

    public function show(\$id)
    {
        \$obj = $modelClassName::find(\$id);
        return response()->json([
            'success' => true,
            'message' => 'Show Success!',
            'data' => \$obj
        ], \$obj ? 200 : 404);
    }

    public function register(Request \$request)
    {
        \$obj = $modelClassName::create(\$request->all());
        return response()->json([
            'success' => true,
            'message' => 'Record created successfully!',
            'data' => \$obj
        ], \$obj ? 201 : 400);
    }

    public function update(Request \$request, \$id)
    {
        \$record = $modelClassName::findOrFail(\$id);
        \$record->update(\$request->all());
        return response()->json([
            'success' => true,
            'message' => 'Record updated successfully!',
            'data' => \$record
        ], 200);
    }

    public function delete(\$id)
    {
        \$obj = $modelClassName::where('$primaryKey', \$id)
            ->update(['recordstatus' => 'DEL']);

        return response()->json([
            'success' => true,
            'message' => 'Record deleted successfully!',
            'data' => null
        ], \$obj ? 200 : 404);
    }
}
EOD;

                $controllerPath = base_path('app/Http/Controllers/' . $controllerFileName);
                File::ensureDirectoryExists(dirname($controllerPath));
                file_put_contents($controllerPath, $controllerContent);
                $this->info("Controller $controllerClassName generated successfully for connection '$connection'.");

                $routes[] = <<<ROUTE
                
// Route for $modelClassName model
\$router->get('/$tableName/list', '{$controllerClassName}@index');
\$router->get('/$tableName/show/{id}', '{$controllerClassName}@show');
\$router->post('/$tableName/register', '{$controllerClassName}@register');
\$router->post('/$tableName/update/{id}', '{$controllerClassName}@update');
\$router->get('/$tableName/delete/{id}', '{$controllerClassName}@delete');
ROUTE;
            }

        } catch (\Exception $e) {
            $this->error('Error generating models or controllers for connection ' . $connection . ': ' . $e->getMessage());
        }
    }

    protected function updateRoutes(array $routes)
    {
        $webPath = base_path('routes/web.php');
        $existingRoutes = file_exists($webPath) ? file($webPath) : [];
        $newRoutes = array_merge($existingRoutes, $routes);
        file_put_contents($webPath, implode(PHP_EOL, $newRoutes));
    }
}
