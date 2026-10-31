<?php

namespace Tests\Feature;

use App\Services\Legacy\DimensiImporter;
use App\Services\Legacy\SqlDumpReader;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'legacy_test', 'database.connections.legacy_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
        $columns = [
            'users' => 'name email password email_verified_at',
            'user_profiles' => 'userid old_id firstname lastname phone is_company companyname position address1 address2 city state zip country bankname bankbranch accountname accountnumber ktp_num aggregator_rate publishing_rate feature_aggregator feature_publishing feature_mcn status reason',
            'releases' => 'profileid type title titlelang primaryartists recordlabel upc aggregator genre subgenre version released releaseddate releaseon finalreleaseddate tiktok_ytmusic_rerelease_date status reason tracknum',
            'tracks' => 'profileid title isrc titlelang genre subgenre version primaryartists featuredartists contributors productions songwriters instrumental lyrics explicit duration',
            'release_track' => 'release_id track_id upc isrc track_order',
            'legacy_import_records' => 'source entity legacy_id target_id status payload source_sha256',
            '_genres' => 'name is_active', '_subgenres' => 'name is_active genreid', '_versions' => 'name is_active',
            '_contributors' => 'name is_active', '_productions' => 'name is_active',
        ];
        foreach ($columns as $name => $fields) {
            Schema::create($name, function ($table) use ($fields) {
                $table->id();
                foreach (explode(' ', $fields) as $field) {
                    if (in_array($field, ['tracknum', 'track_order', 'legacy_id', 'target_id', 'userid', 'profileid', 'track_id', 'release_id'])) {
                        $table->integer($field)->nullable();
                    } else {
                        $table->text($field)->nullable();
                    }
                }
                $table->timestamps();
            });
        }
    }

    protected function tearDown(): void
    {
        DB::purge('legacy_test');
        parent::tearDown();
    }

    protected function data(): array
    {
        $d = array_fill_keys(DimensiImporter::TABLES, []);
        $u = array_fill_keys(explode(' ', 'id role email password_hash password full_name username created_at updated_at country account_type type status phone company_name address subdistrict district city province postal_code bank_name bank_account_name bank_account_holder bank_account_number nik aggregator_percentage publishing_percentage block_reason rejection_reason'), null);
        $d['users'][7] = array_merge($u, ['id' => '7', 'role' => 'User', 'email' => 'legacy@example.test', 'password_hash' => preg_replace('/^\$2y\$/', '\$2b\$', password_hash('LegacyTest!123', PASSWORD_BCRYPT)), 'full_name' => 'Legacy Client', 'country' => 'Indonesia', 'status' => 'Approved', 'aggregator_percentage' => '69.99', 'publishing_percentage' => '70.00']);
        $r = array_fill_keys(explode(' ', 'id user_id upc status type title language primary_artists label aggregator genre sub_genre genre_id subgenre_id version original_release_date planned_release_date pre_release_youtube_music pre_release_social_media rejection_reason rejection_description created_at updated_at cover_art cover_url'), null);
        $d['releases'][11] = array_merge($r, ['id' => '11', 'user_id' => '7', 'upc' => '1234567890123', 'status' => 'Live', 'type' => 'Single', 'title' => 'Legacy Song', 'language' => 'Indonesia', 'primary_artists' => '[{"name":"Artist","spotifyLink":"https://open.spotify.com/artist/example"}]']);
        $t = array_fill_keys(explode(' ', 'id release_id isrc title language genre sub_genre version primary_artists featured_artists contributors producer writer lyricist is_instrumental lyrics explicit_lyrics explicit duration created_at updated_at track_number audio_file audio_clip ipl_file'), null);
        $d['tracks'][12] = array_merge($t, ['id' => '12', 'release_id' => '11', 'isrc' => 'IDABC2600001', 'title' => 'Legacy Song', 'writer' => '[{"name":"Writer"}]', 'lyricist' => '["Writer"]']);
        return $d;
    }

    public function test_import_preserves_ownership_bcrypt_decimal_credits_and_is_idempotent(): void
    {
        $d = $this->data();
        DB::beginTransaction();
        (new DimensiImporter)->run($d, str_repeat('a', 64));
        $user = DB::table('users')->first();
        $this->assertTrue(Hash::check('LegacyTest!123', $user->password));
        $profile = DB::table('user_profiles')->first();
        $this->assertSame('69.99', $profile->aggregator_rate);
        $release = DB::table('releases')->first();
        $track = DB::table('tracks')->first();
        $this->assertSame($profile->id, $release->profileid);
        $this->assertSame($profile->id, $track->profileid);
        $this->assertSame('released', $release->status);
        $this->assertSame('author_composer', json_decode($track->songwriters, true)[0]['role']);
        $this->assertSame('https://open.spotify.com/artist/example', json_decode($track->primaryartists, true)[0]['spotify']);
        $archive = DB::table('legacy_import_records')->where('entity', 'users')->first();
        $this->assertSame($d['users'][7], unserialize(Crypt::decryptString($archive->payload), ['allowed_classes' => false]));
        $this->assertStringNotContainsString('legacy@example.test', $archive->payload);
        (new DimensiImporter)->run($d, str_repeat('a', 64));
        $this->assertSame(1, DB::table('users')->count());
        $this->assertSame(1, DB::table('tracks')->count());
        $this->assertSame(1, DB::table('release_track')->count());
        DB::rollBack();
        $this->assertSame(0, DB::table('users')->count());
    }

    public function test_existing_email_and_admin_are_quarantined_without_overwrite(): void
    {
        $d = $this->data();
        DB::table('users')->insert(['email' => 'LEGACY@example.test', 'password' => 'keep-original']);
        $d['users'][8] = array_merge($d['users'][7], ['id' => '8', 'role' => 'Admin', 'email' => 'admin-old@example.test']);
        DB::transaction(fn () => (new DimensiImporter)->run($d, str_repeat('b', 64)));
        $this->assertSame(1, DB::table('users')->count());
        $this->assertSame('keep-original', DB::table('users')->first()->password);
        $this->assertSame(0, DB::table('releases')->count());
        $this->assertSame(1, DB::table('legacy_import_records')->where('status', 'admin_not_imported')->count());
        $this->assertSame(1, DB::table('legacy_import_records')->where('status', 'email_conflict_skipped')->count());
    }

    public function test_changed_source_row_is_rejected_and_rolls_back(): void
    {
        $d = $this->data();
        DB::transaction(fn () => (new DimensiImporter)->run($d, str_repeat('a', 64)));
        $d['users'][7]['phone'] = 'changed';
        $this->expectException(\RuntimeException::class);
        DB::transaction(fn () => (new DimensiImporter)->run($d, str_repeat('b', 64)));
    }

    public function test_parser_reads_escaped_literals_without_executing_sql(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'legacy-parser-');
        try {
            file_put_contents($path, "DROP TABLE users;\nINSERT INTO `users` (`id`, `name`, `note`) VALUES\n(1, 'O\\'Brien, (test);', 'line\\nnext'),\n(2, 'Two''s', NULL);\n");
            $rows = (new SqlDumpReader)->read($path, ['users']);
            $this->assertSame("O'Brien, (test);", $rows['users'][1]['name']);
            $this->assertSame("line\nnext", $rows['users'][1]['note']);
            $this->assertSame("Two's", $rows['users'][2]['name']);
            $this->assertNull($rows['users'][2]['note']);
        } finally {
            unlink($path);
        }
    }
}
