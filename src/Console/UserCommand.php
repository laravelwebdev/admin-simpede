<?php

namespace Laravel\Nova\Console;

use Illuminate\Console\Command;
use Laravel\Nova\Nova;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'nova:user')]
class UserCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'nova:user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        Nova::createUser($this);

        $this->components->info('User created successfully.');
    }
}
