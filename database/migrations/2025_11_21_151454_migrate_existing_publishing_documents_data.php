<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Migrate existing statement and nuon files from direct morphTo relationship
     * to the new publishing_documents table structure
     */
    public function up(): void
    {
        // Find all files that are statements or nuon docs attached to publishings
        $existingDocs = DB::table('filestorage')
            ->where('owner_type', 'App\Models\Publishing\Publishing')
            ->whereIn('type', ['statement', 'nuon'])
            ->get();

        foreach ($existingDocs as $file) {
            // Create a document record
            $documentId = DB::table('publishing_documents')->insertGetId([
                'publishingid' => $file->owner_id,
                'profileid' => $file->profileid,
                'type' => $file->type,
                'issued_at' => $file->uploaded_at ?? $file->created_at,
                'created_at' => $file->created_at,
                'updated_at' => $file->updated_at,
            ]);

            // Update the file to point to the new document
            DB::table('filestorage')
                ->where('id', $file->id)
                ->update([
                    'owner_type' => 'App\Models\Publishing\Document',
                    'owner_id' => $documentId,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * Restore files back to direct publishing relationship
     */
    public function down(): void
    {
        // Find all documents
        $documents = DB::table('publishing_documents')->get();

        foreach ($documents as $doc) {
            // Find the file associated with this document
            $file = DB::table('filestorage')
                ->where('owner_type', 'App\Models\Publishing\Document')
                ->where('owner_id', $doc->id)
                ->first();

            if ($file) {
                // Restore file to point directly to publishing
                DB::table('filestorage')
                    ->where('id', $file->id)
                    ->update([
                        'owner_type' => 'App\Models\Publishing\Publishing',
                        'owner_id' => $doc->publishingid,
                    ]);
            }
        }

        // Delete all document records
        DB::table('publishing_documents')->truncate();
    }
};
