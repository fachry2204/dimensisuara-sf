<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\System\Genre;
use App\Models\System\Subgenre;
use App\Models\System\Production;
use App\Models\System\Version;
use App\Models\System\Contributor;

class SystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Spelling Mapping to resolve typos / casing on existing records first
        $spellingMap = [
            'version' => [
                'Accoustic' => 'Acoustic',
                'Remaster' => 'Remastered',
            ],
            'contributor' => [
                'Basson' => 'Bassoon',
                'Viola Da Gamba' => 'Viola da gamba',
            ],
            'production' => [
                'Co Producer' => 'Co-Producer',
            ],
            'genre' => [
                'Alternative/Indie' => 'alternative/indie',
                'Latin' => 'latin',
                'Hip-Hop/Rap' => 'Hip Hop/Rap',
            ],
            'subgenre' => [
                'Buddhist Music' => 'Buddhist music',
                'Deep Pop EDM' => 'Deep Pop Edm',
                'Lo-fi House' => 'Lo-Fi House',
                'Lo-fi' => 'Lo-Fi',
                'Pakistani Folk Music' => 'Pakistani folk music',
                'Sheilat and Khaleeji' => 'Sheilat and khaleeji',
                'Sufi Music' => 'Sufi music',
                'J-Pop' => 'J Pop',
                'Hardcore' => 'Hard Core',
            ]
        ];

        // Apply spelling mappings
        foreach ($spellingMap['version'] as $old => $new) {
            Version::where('name', $old)->update(['name' => $new]);
        }
        foreach ($spellingMap['contributor'] as $old => $new) {
            Contributor::where('name', $old)->update(['name' => $new]);
        }
        foreach ($spellingMap['production'] as $old => $new) {
            Production::where('name', $old)->update(['name' => $new]);
        }
        foreach ($spellingMap['genre'] as $old => $new) {
            Genre::where('name', $old)->update(['name' => $new]);
        }
        foreach ($spellingMap['subgenre'] as $old => $new) {
            Subgenre::where('name', $old)->update(['name' => $new]);
        }

        // 2. Load JSON data
        $dataPath = database_path('seeders/data/');
        $versionsJson = json_decode(file_get_contents($dataPath . 'version.json'), true);
        $contributorsJson = json_decode(file_get_contents($dataPath . 'contributor.json'), true);
        $productionsJson = json_decode(file_get_contents($dataPath . 'production.json'), true);
        $genresJson = json_decode(file_get_contents($dataPath . 'genre.json'), true);

        // 3. Process Versions
        // We must keep "Original" active, although it's not in the version.json
        $activeVersions = ['Original'];
        foreach ($versionsJson as $name) {
            $activeVersions[] = $name;
        }

        foreach ($activeVersions as $name) {
            Version::updateOrCreate(
                ['name' => $name],
                ['is_active' => true]
            );
        }

        // Deactivate versions not in JSON and not "Original"
        Version::whereNotIn('name', $activeVersions)->update(['is_active' => false]);


        // 4. Process Contributors
        // Note: we want to deprecate "Other", which is NOT in contributor.json anyway
        $activeContributors = [];
        foreach ($contributorsJson as $name) {
            if ($name !== 'Other') {
                $activeContributors[] = $name;
                Contributor::updateOrCreate(
                    ['name' => $name],
                    ['is_active' => true]
                );
            }
        }
        // Deactivate others
        Contributor::whereNotIn('name', $activeContributors)->update(['is_active' => false]);


        // 5. Process Productions
        $activeProductions = [];
        foreach ($productionsJson as $name) {
            $activeProductions[] = $name;
            Production::updateOrCreate(
                ['name' => $name],
                ['is_active' => true]
            );
        }
        // Deactivate others
        Production::whereNotIn('name', $activeProductions)->update(['is_active' => false]);


        // 6. Process Genres & Subgenres
        // Note: we want to deprecate "Other" genre
        $activeGenres = [];
        $activeSubgenresByGenre = [];

        foreach ($genresJson as $genreName => $subgenresArray) {
            if ($genreName !== 'Other') {
                $activeGenres[] = $genreName;
                $genre = Genre::updateOrCreate(
                    ['name' => $genreName],
                    ['is_active' => true]
                );

                $activeSubgenresByGenre[$genre->id] = [];

                foreach ($subgenresArray as $subName) {
                    $activeSubgenresByGenre[$genre->id][] = $subName;
                    Subgenre::updateOrCreate(
                        [
                            'name' => $subName,
                            'genreid' => $genre->id
                        ],
                        ['is_active' => true]
                    );
                }

                // Deactivate subgenres of this genre that are not in the new JSON
                Subgenre::where('genreid', $genre->id)
                    ->whereNotIn('name', $activeSubgenresByGenre[$genre->id])
                    ->update(['is_active' => false]);
            }
        }

        // Deactivate genres not in JSON and not "Other"
        Genre::whereNotIn('name', $activeGenres)->update(['is_active' => false]);
        
        // Deactivate subgenres belonging to deactivated genres
        $deactivatedGenreIds = Genre::where('is_active', false)->pluck('id');
        Subgenre::whereIn('genreid', $deactivatedGenreIds)->update(['is_active' => false]);

        // Permanently delete "N/A" subgenres
        Subgenre::where('name', 'N/A')->delete();
    }
}
