<?php

namespace Cuongnd88\OtpAuth\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;

class MakeOtpAuth extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:otp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate files in authenticating otp';

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
        if (parent::handle() === false) {
            return false;
        }
    }

        /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/otp-auth.stub';
    }
}