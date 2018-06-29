<?php

<<<<<<< HEAD
namespace Dwij\Laraadmin\Commands;
=======
namespace Dwij\LaradminCommands;
>>>>>>> aef8cb55e536e158f387f2a82498a6467c05a84d

use Config;
use Artisan;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
<<<<<<< HEAD
use Dwij\Laraadmin\Models\Module;
use Dwij\Laraadmin\CodeGenerator;

=======
use Dwij\LaradminModels\Module;
use Dwij\LaradminCodeGenerator;

/**
 * Class Crud
 * @package Dwij\LaradminCommands
 *
 * Command that generates CRUD's for a Module. Takes Module name as input.
 */
>>>>>>> aef8cb55e536e158f387f2a82498a6467c05a84d
class Crud extends Command
{
    /**
     * The command signature.
     *
     * @var string
     */
    protected $signature = 'la:crud {module}';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Generate CRUD Methods for given Module.';
    
    /* ================ Config ================ */
    var $module = null;
    var $controllerName = "";
    var $modelName = "";
    var $moduleName = "";
    var $dbTableName = "";
    var $singularVar = "";
    var $singularCapitalVar = "";
    
    /**
     * Generate a CRUD files inclusing Controller, Model and Routes
     *
     * @return mixed
     */
    public function handle()
    {
        $module = $this->argument('module');
        
        try {
            
            $config = CodeGenerator::generateConfig($module, "fa-cube");
            
            CodeGenerator::createController($config, $this);
            CodeGenerator::createModel($config, $this);
            CodeGenerator::createViews($config, $this);
            CodeGenerator::appendRoutes($config, $this);
            CodeGenerator::addMenu($config, $this);
            
        } catch (Exception $e) {
            $this->error("Crud::handle exception: ".$e);
            throw new Exception("Unable to generate migration for ".($module)." : ".$e->getMessage(), 1);
        }
        $this->info("\nCRUD successfully generated for ".($module)."\n");
    }
}
