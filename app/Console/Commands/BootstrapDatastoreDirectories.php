<?php

namespace Poniverse\Ponyfm\Console\Commands;

use Illuminate\Console\Command;

class BootstrapDatastoreDirectories extends Command
{
    protected $signature = 'bootstrap:datastore-dir';

    protected $description = 'Creates the datastore directories used by the system';

    public function handle()
    {
        if (!is_dir(config('ponyfm.files_directory').'/tmp')) {
            mkdir(config('ponyfm.files_directory').'/tmp', 0755, true);
        }

        if (!is_dir(config('ponyfm.files_directory').'/queued-tracks')) {
            mkdir(config('ponyfm.files_directory').'/queued-tracks', 0755, true);
        }

        $this->output->success('Done');
    }
}
