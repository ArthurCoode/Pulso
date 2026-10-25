<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:dispatch-due-checks')]
#[Description('Command description')]
class DispatchDueChecks extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
