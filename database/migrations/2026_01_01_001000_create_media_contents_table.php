<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        // Image bytes are kept in the database, in their own table so that listing
        // media never loads the files themselves.
        Schema::create('media_contents', function (Blueprint $table) {
            $table->foreignId('media_id')->primary()->constrained('media')->cascadeOnDelete();
            $table->binary('data');
        });

        // MySQL's BLOB holds only 64 KB; MEDIUMBLOB holds up to 16 MB.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE media_contents MODIFY data MEDIUMBLOB NOT NULL');
        }

        // Copy images uploaded before this change from the storage folder into the database.
        $disk = Storage::disk(config('innes.uploads.disk', 'public'));
        foreach (DB::table('media')->select('id', 'path')->get() as $media) {
            if ($disk->exists($media->path)) {
                DB::table('media_contents')->insert(['media_id' => $media->id, 'data' => $disk->get($media->path)]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('media_contents');
    }
};
