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
        $name = $this->qualifyClass($this->getNameInput());
        $path = $this->getPath($name);
        $this->makeDirectory($path);

        $this->files->put($path, $this->sortImports($this->buildClass($name)));
        $this->generateOtpAuthTrait($path, explode('/', $this->getNameInput()));

        $this->migrateNotificationTable();

        $this->info($this->type.' created successfully.');
    }

    /**
     * Migrate Notification table
     *
     * @return void
     */
    public function migrateNotificationTable()
    {
        if (empty(glob(base_path().'/database/migrations/*_create_notifications_table.php'))) {
            $this->call('notifications:table');
        }
    }

    /**
     * Generate Otp Auth Trait
     *
     * @param  string $path
     * @param  string $nameInput
     *
     * @return void
     */
    public function generateOtpAuthTrait($path, $nameInput)
    {
        list($path, $name, $otpAuthClass) = $this->qualifyTrait($path, $nameInput);
        $this->files->put($path, $this->sortImports($this->buildTrait($name, $otpAuthClass)));
    }

    /**
     * To qualify Trait
     *
     * @param  string $path
     * @param  string $nameInput
     *
     * @return array
     */
    public function qualifyTrait($path, $nameInput)
    {
        $otpAuthClass = array_pop($nameInput);
        $name = $this->qualifyClass(implode($nameInput).'/'.$this->traitName);
        $path = str_replace($otpAuthClass, $this->traitName, $path);
        return [$path, $name, $otpAuthClass];
    }

    /**
     * Build Trait
     *
     * @param  string $name
     * @param  string $otpAuthClass
     *
     * @return string
     */
    protected function buildTrait($name, $otpAuthClass)
    {
        $stub = $this->files->get($this->getTraitStub());

        return $this->replaceNamespaceTrait($stub, $name, $otpAuthClass);
    }

    /**
     * Replace Namespace Trait
     *
     * @param  string $stub
     * @param  string $name
     * @param  string $otpAuthClass
     *
     * @return string
     */
    protected function replaceNamespaceTrait(&$stub, $name, $otpAuthClass)
    {
        return str_replace(
            ['DummyNamespace', 'DummyOtpAuthClass'],
            [$this->getNamespace($name), $otpAuthClass],
            $stub
        );
    }

    /**
     * Get Trait Stub
     *
     * @return string
     */
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