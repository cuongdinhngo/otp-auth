<?php

namespace Cuongnd88\OtpAuth\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;

class MakeOtpAuthCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:otp {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate files in authenticating otp';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'OTP Authentication';

    protected $traitName = 'HasOtpAuth';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    // public function __construct()
    // {
    //     parent::__construct();
    // }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info(__METHOD__);
        $name = $this->qualifyClass($this->getNameInput());
        $path = $this->getPath($name);
        $this->makeDirectory($path);

        $this->files->put($path, $this->sortImports($this->buildClass($name)));
        $this->generateOtpAuthTrait($path, explode('/', $this->getNameInput()));

        $this->migrateNotificationTable();

        $this->info($this->type.' created successfully.');
    }

    public function migrateNotificationTable()
    {
        if (empty(glob(base_path().'/database/migrations/*_create_notifications_table.php'))) {
            $this->call('notifications:table');
        }
    }

    public function generateOtpAuthTrait($path, $nameInput)
    {
        list($path, $name, $otpAuthClass) = $this->qualifyTrait($path, $nameInput);
        $this->files->put($path, $this->sortImports($this->buildTrait($name, $otpAuthClass)));
    }

    public function qualifyTrait($path, $nameInput)
    {
        $otpAuthClass = array_pop($nameInput);
        $name = $this->qualifyClass(implode($nameInput).'/'.$this->traitName);
        $path = str_replace($otpAuthClass, $this->traitName, $path);
        return [$path, $name, $otpAuthClass];
    }

    protected function buildTrait($name, $otpAuthClass)
    {
        $stub = $this->files->get($this->getTraitStub());

        return $this->replaceNamespaceTrait($stub, $name, $otpAuthClass);
    }

    protected function replaceNamespaceTrait(&$stub, $name, $otpAuthClass)
    {
        return str_replace(
            ['DummyNamespace', 'DummyOtpAuthClass'],
            [$this->getNamespace($name), $otpAuthClass],
            $stub
        );
    }

    protected function getTraitStub()
    {
        return __DIR__.'/../stubs/has-otp-auth-trait.stub';
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/../stubs/otp-auth.stub';
    }
}