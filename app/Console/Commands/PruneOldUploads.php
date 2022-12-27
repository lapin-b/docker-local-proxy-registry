<?php

namespace App\Console\Commands;

use App\Models\PendingContainerLayer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToDeleteFile;

class PruneOldUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'registry:prune-uploads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune old pending uploads';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $old_uploads = PendingContainerLayer::where(
            'updated_at',
            '<',
            now()->subtract('seconds', 30)
        )->get();

        $storage = Storage::drive('local');

        foreach($old_uploads as $old_upload){
            $this->info("Deleting upload $old_upload->id");
            try{
                $storage->getAdapter()->delete($old_upload->rel_upload_path);
                $old_upload->delete();
            }catch (UnableToDeleteFile $e){
                $this->error("Unable to delete $old_upload->rel_upload_path: {$e->getMessage()}");
            }
        }

        return Command::SUCCESS;
    }
}
