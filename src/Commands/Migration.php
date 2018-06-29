<?php

<<<<<<< HEAD
namespace Dwij\Laraadmin\Commands;

use Illuminate\Console\Command;

use Dwij\Laraadmin\CodeGenerator;

=======
namespace Dwij\LaradminCommands;

use Illuminate\Console\Command;

use Dwij\LaradminCodeGenerator;

/**
 * Class Migration
 * @package Dwij\LaradminCommands
 *
 * Command to generation new sample migration file or complete migration file from DB Context
 * if '--generate' parameter is used after command, it generate migration from database.
 */
>>>>>>> aef8cb55e536e158f387f2a82498a6467c05a84d
class Migration extends Command
{
    /**
     * The command signature.
     *
     * @var string
     */
    protected $signature = 'la:migration {table} {--generate}';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Genrate Migrations for LaraAdmin';

    /**
     * Generate a CRUD files inclusing Controller, Model and Routes
     *
     * @return mixed
     */
    public function handle()
    {
        $table = $this->argument('table');
        $generateFromTable = $this->option('generate');
        if($generateFromTable) {
            $generateFromTable = true;
        }
        CodeGenerator::generateMigration($table, $generateFromTable, $this);
    }
}
