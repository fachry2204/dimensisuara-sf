<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\Intl\Countries;

class DimensiImporter
{
    public const TABLES = ['users', 'releases', 'tracks', 'genres', 'subgenres', 'record_labels',
        'release_uploads', 'track_artists', 'track_contributors', 'track_additional_writers',
        'track_lyricists', 'track_production_credits', 'track_songwriters'];

    private const SOURCE = 'dimensisuara_legacy';
    private array $stats = [];
    private string $hash;
    private array $data;
    private array $profiles = [];
    private array $releases = [];
    private array $trackProfiles = [];
    private array $existingUpcs = [];
    private array $existingIsrcs = [];
    private array $seenCodes = [];

    public function run(array $data, string $hash): array
    {
        if (DB::transactionLevel() === 0) {
            throw new RuntimeException('Import must run inside a transaction.');
        }
        $this->data = $data;
        $this->hash = $hash;
        $this->stats = [];
        $this->profiles = $this->releases = $this->trackProfiles = $this->seenCodes = [];
        $this->existingUpcs = $this->existingCodes('releases', 'upc');
        $this->existingIsrcs = $this->existingCodes('tracks', 'isrc');
        foreach ($data['users'] as $row) {
            $this->user($row);
        }
        foreach ($data['releases'] as $row) {
            $this->release($row);
        }
        foreach ($data['tracks'] as $row) {
            $this->track($row);
        }
        foreach (self::TABLES as $table) {
            if (in_array($table, ['users', 'releases', 'tracks'], true)) {
                continue;
            }
            foreach ($data[$table] as $row) {
                if (!$this->previous($table, $row)) {
                    $this->record($table, $row, null, 'archived');
                }
            }
        }
        return $this->stats;
    }

    private function user(array $r): void
    {
        if ($prior = $this->previous('users', $r)) {
            if ($prior->status === 'imported') {
                $profile = DB::table('user_profiles')->where('userid', $prior->target_id)->first();
                if (!$profile) {
                    throw new RuntimeException('Previously imported profile is missing.');
                }
                $this->profiles[$r['id']] = $profile->id;
            }
            return;
        }
        if (in_array(strtolower($r['role'] ?? ''), ['admin', 'operator', 'finance'], true)) {
            $this->record('users', $r, null, 'admin_not_imported');
            return;
        }
        $email = strtolower(trim($r['email']));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->record('users', $r, null, 'invalid_email');
            return;
        }
        if (DB::table('users')->whereRaw('LOWER(email) = ?', [$email])->exists()
            || (\Illuminate\Support\Facades\Schema::hasTable('user_admin') && DB::table('user_admin')->whereRaw('LOWER(email) = ?', [$email])->exists())) {
            $this->record('users', $r, null, 'email_conflict_skipped');
            return;
        }
        $password = $r['password_hash'] ?: ($r['password'] ?? '');
        // Node bcrypt's $2b$ hashes are compatible with PHP bcrypt's $2y$ prefix.
        if (str_starts_with($password, '$2b$')) {
            $password = '$2y$'.substr($password, 4);
        }
        if ((password_get_info($password)['algoName'] ?? 'unknown') !== 'bcrypt') {
            $this->record('users', $r, null, 'unsupported_password');
            return;
        }
        $name = trim($r['full_name'] ?: $r['username']);
        $parts = preg_split('/\s+/', $name, 2);
        $now = now();
        $created = $r['created_at'] ?: $now;
        $userId = DB::table('users')->insertGetId([
            'name' => $name, 'email' => $email, 'password' => $password,
            'email_verified_at' => null, 'created_at' => $created, 'updated_at' => $r['updated_at'] ?: $created,
        ]);
        $countryName = strtolower(trim($r['country'] ?? ''));
        $country = strlen($countryName) === 2 && Countries::exists(strtoupper($countryName)) ? strtoupper($countryName) : array_search($countryName, array_map('strtolower', Countries::getNames('en')), true);
        if (!$country) {
            $country = 'ID';
            $this->increment('country_defaulted');
        }
        $company = ($r['account_type'] ?? '') === 'COMPANY' || ($r['type'] ?? '') === 'Perusahaan';
        $status = match (strtolower($r['status'])) {
            'approved', 'active' => 'verified',
            'blocked', 'inactive' => 'blocked',
            default => 'pending',
        };
        $profileId = DB::table('user_profiles')->insertGetId([
            'userid' => $userId, 'old_id' => 'dimensisuara:'.$r['id'],
            'firstname' => $parts[0], 'lastname' => $parts[1] ?? null,
            'phone' => $r['phone'] ?? '', 'is_company' => $company,
            'companyname' => $r['company_name'], 'position' => $company ? 'direktur_utama' : null,
            'address1' => $r['address'] ?? '',
            'address2' => implode(', ', array_filter([$r['subdistrict'], $r['district']])),
            'city' => $r['city'], 'state' => $r['province'], 'zip' => $r['postal_code'] ?? '', 'country' => $country,
            'bankname' => $r['bank_name'] ?? '', 'bankbranch' => '',
            'accountname' => $r['bank_account_name'] ?: ($r['bank_account_holder'] ?? ''),
            'accountnumber' => $r['bank_account_number'] ?? '', 'ktp_num' => $r['nik'],
            'aggregator_rate' => $this->rate($r['aggregator_percentage']),
            'publishing_rate' => $this->rate($r['publishing_percentage']),
            'feature_aggregator' => true, 'feature_publishing' => false, 'feature_mcn' => false,
            'status' => $status, 'reason' => $r['block_reason'] ?: $r['rejection_reason'],
            'created_at' => $created, 'updated_at' => $r['updated_at'] ?: $created,
        ]);
        $this->profiles[$r['id']] = $profileId;
        $this->record('users', $r, $userId, 'imported');
    }

    private function release(array $r): void
    {
        if ($prior = $this->previous('releases', $r)) {
            if ($prior->status === 'imported') {
                $existing = DB::table('releases')->where('id', $prior->target_id)->first();
                if (!$existing) {
                    throw new RuntimeException('Previously imported release is missing.');
                }
                $this->releases[$r['id']] = $existing->id;
                $this->trackProfiles[$r['id']] = $existing->profileid;
            }
            return;
        }
        if (!isset($this->profiles[$r['user_id']])) {
            $this->record('releases', $r, null, 'owner_unavailable');
            return;
        }
        $upc = trim($r['upc'] ?? '') ?: null;
        if ($upc && isset($this->existingUpcs[strtolower($upc)])) {
            $this->record('releases', $r, null, 'upc_conflict_skipped');
            return;
        }
        $status = match (strtolower($r['status'])) {
            'released', 'live' => 'released', 'processing' => 'uploading',
            'request edit' => 'revision', 'rejected' => 'rejected', 'pending' => 'pending',
            default => null,
        };
        if (!$status) {
            $this->record('releases', $r, null, 'unknown_status');
            return;
        }
        $profileId = $this->profiles[$r['user_id']];
        $this->noteRepeatedCode('upc', $upc);
        $id = \App\Database\RelationalFields::insert('releases', [
            'profileid' => $profileId, 'type' => strtolower($r['type']) === 'single' ? 'single' : 'album',
            'title' => $r['title'], 'titlelang' => $this->language($r['language']),
            'primaryartists' => $this->json($this->artists($r['primary_artists'])),
            'recordlabel' => $r['label'], 'upc' => $upc, 'aggregator' => $r['aggregator'],
            'genre' => $this->genre($r), 'subgenre' => $this->subgenre($r),
            'version' => $this->lookup('_versions', $r['version'] ?: 'Original'),
            'released' => $r['original_release_date'] ? 1 : 0,
            'releaseddate' => $this->date($r['original_release_date']),
            'releaseon' => $this->date($r['planned_release_date']),
            'finalreleaseddate' => $status === 'released' ? $this->date($r['planned_release_date']) : null,
            'tiktok_ytmusic_rerelease_date' => $this->date($r['pre_release_youtube_music'] ?: $r['pre_release_social_media']),
            'status' => $status, 'reason' => trim(implode("\n", array_filter([$r['rejection_reason'], $r['rejection_description']]))) ?: null,
            'tracknum' => 0, 'created_at' => $r['created_at'], 'updated_at' => $r['updated_at'] ?: $r['created_at'],
        ]);
        $this->releases[$r['id']] = $id;
        $this->trackProfiles[$r['id']] = $profileId;
        $this->record('releases', $r, $id, 'imported');
        if ($r['cover_art'] ?: $r['cover_url']) {
            $this->increment('cover_files_pending');
        }
    }

    private function track(array $r): void
    {
        if ($this->previous('tracks', $r)) {
            return;
        }
        if (!isset($this->releases[$r['release_id']])) {
            $this->record('tracks', $r, null, 'release_unavailable');
            return;
        }
        $releaseId = $this->releases[$r['release_id']];
        $release = $this->data['releases'][$r['release_id']];
        // Keep distinct source track IDs even when recordings share an ISRC.
        // Collisions with the pre-existing destination catalogue require review.
        $isrc = trim($r['isrc'] ?? '') ?: null;
        if ($isrc && isset($this->existingIsrcs[strtolower($isrc)])) {
            $this->record('tracks', $r, null, 'isrc_conflict_skipped');
            return;
        }
        $writers = $this->writers($r);
        $this->noteRepeatedCode('isrc', $isrc);
        $id = \App\Database\RelationalFields::insert('tracks', [
            'profileid' => $this->trackProfiles[$r['release_id']], 'title' => $r['title'], 'isrc' => $isrc,
            'titlelang' => $this->language($r['language'] ?: $release['language']),
            'genre' => $this->genre($r) ?: $this->genre($release),
            'subgenre' => $this->subgenre($r) ?: $this->subgenre($release),
            'version' => $this->lookup('_versions', $r['version'] ?: 'Original'),
            'primaryartists' => $this->json($this->artists($r['primary_artists'] ?: $release['primary_artists'])),
            'featuredartists' => $this->json($this->artists($r['featured_artists'])),
            'contributors' => $this->json($this->credits($r, false)),
            'productions' => $this->json($this->credits($r, true)),
            'songwriters' => $this->json($writers), 'instrumental' => (bool) $r['is_instrumental'],
            'lyrics' => $r['lyrics'], 'explicit' => ($r['explicit_lyrics'] ?? $r['explicit'] ?? 0) ? 'explicit' : 'non-explicit',
            'duration' => $r['duration'] === null ? null : max(0, (int) $r['duration']),
            'created_at' => $r['created_at'], 'updated_at' => $r['updated_at'] ?: $r['created_at'],
        ]);
        $order = max(1, (int) ($r['track_number'] ?: (DB::table('release_track')->where('release_id', $releaseId)->count() + 1)));
        DB::table('release_track')->insert([
            'release_id' => $releaseId, 'track_id' => $id, 'upc' => $release['upc'], 'isrc' => $isrc,
            'track_order' => $order, 'created_at' => $r['created_at'], 'updated_at' => $r['updated_at'] ?: $r['created_at'],
        ]);
        DB::table('releases')->where('id', $releaseId)->increment('tracknum');
        $this->record('tracks', $r, $id, 'imported');
        foreach (['audio_file', 'audio_clip', 'ipl_file'] as $field) {
            if ($r[$field]) {
                $this->increment($field.'_pending');
            }
        }
    }

    private function artists(?string $json): array
    {
        $result = [];
        foreach ($this->decode($json) as $artist) {
            if (is_string($artist)) {
                $artist = ['name' => $artist];
            }
            if (!empty($artist['name'])) {
                $result[] = ['name' => $artist['name'], 'spotify' => $artist['spotifyLink'] ?? $artist['spotify'] ?? null, 'social' => $artist['socialLink'] ?? $artist['social'] ?? null];
            }
        }
        return $result;
    }

    private function writers(array $r): array
    {
        $result = [];
        $sets = [[$this->decode($r['writer']), 'composer'], [$this->decode($r['lyricist']), 'author'],
            [$this->related('track_songwriters', $r['id']), 'composer'], [$this->related('track_lyricists', $r['id']), 'author']];
        foreach ($sets as [$rows, $role]) {
            foreach ($rows as $writer) {
                $name = trim(is_string($writer) ? $writer : ($writer['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $key = mb_strtolower($name);
                $existing = $result[$key]['role'] ?? $role;
                $result[$key] = ['name' => $name, 'role' => $existing === $role ? $role : 'author_composer'];
            }
        }
        foreach ($this->related('track_additional_writers', $r['id']) as $writer) {
            if (!empty($writer['name'])) {
                $name = trim($writer['name']);
                $result[mb_strtolower($name)] ??= ['name' => $name, 'role' => 'author_composer'];
            }
        }
        return array_values($result);
    }

    private function credits(array $r, bool $production): array
    {
        $rows = array_merge($this->decode($r[$production ? 'producer' : 'contributors']), $this->related($production ? 'track_production_credits' : 'track_contributors', $r['id']));
        $result = [];
        foreach ($rows as $credit) {
            if (!is_array($credit) || empty($credit['name'])) {
                continue;
            }
            $role = $credit['roleName'] ?? $credit['role_name'] ?? $credit['role'] ?? $credit['type'] ?? ($production ? 'Producer' : 'Other');
            $roleId = $this->lookup($production ? '_productions' : '_contributors', $role);
            $result[mb_strtolower($credit['name']).':'.$roleId] = ['name' => $credit['name'], 'role' => $roleId];
        }
        return array_values($result);
    }

    private function related(string $table, string $trackId): array
    {
        $rows = array_filter($this->data[$table], fn ($r) => $r['track_id'] == $trackId);
        uasort($rows, fn ($a, $b) => ($a['sequence_number'] ?? 0) <=> ($b['sequence_number'] ?? 0));
        return array_values($rows);
    }

    private function genre(array $r): ?int
    {
        $name = $r['genre'] ?: ($this->data['genres'][$r['genre_id'] ?? '']['name'] ?? null);
        return $this->lookup('_genres', $name);
    }

    private function subgenre(array $r): ?int
    {
        $name = $r['sub_genre'] ?: ($this->data['subgenres'][$r['subgenre_id'] ?? '']['name'] ?? null);
        $genre = $this->genre($r);
        if (!$name || !$genre) {
            return null;
        }
        return $this->lookup('_subgenres', $name, ['genreid' => $genre]);
    }

    private function lookup(string $table, ?string $name, array $extra = []): ?int
    {
        if (!trim($name ?? '')) {
            return null;
        }
        $query = DB::table($table)->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))]);
        foreach ($extra as $key => $value) {
            $query->where($key, $value);
        }
        return $query->value('id') ?? DB::table($table)->insertGetId(array_merge($extra, ['name' => trim($name), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]));
    }

    private function language(?string $value): ?string
    {
        return match (strtolower(trim($value ?? ''))) {
            'indonesia', 'indonesian', 'id' => 'id',
            'united states', 'english', 'en' => 'en',
            'argentina', 'spanish', 'es' => 'es',
            '', 'null' => null,
            default => throw new RuntimeException('Unmapped legacy language.'),
        };
    }

    private function date(?string $value): ?string
    {
        return $value && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value) && $value !== '0000-00-00' ? $value : null;
    }

    private function rate(?string $value): string
    {
        if (!is_numeric($value) || (float) $value < 0 || (float) $value > 100) {
            throw new RuntimeException('Invalid legacy revenue percentage.');
        }
        return number_format((float) $value, 2, '.', '');
    }

    private function decode(?string $value): array
    {
        if (!$value) {
            return [];
        }
        $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new RuntimeException('Expected legacy array.');
        }
        return $decoded;
    }

    private function json(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function previous(string $entity, array $row): ?object
    {
        $record = DB::table('legacy_import_records')->where(['source' => self::SOURCE, 'entity' => $entity, 'legacy_id' => $row['id']])->first();
        if ($record) {
            // Never silently accept changed source records under an existing mapping.
            $original = \App\Database\RelationalFields::legacyPayload($record);
            if ($original !== $row) {
                throw new RuntimeException('Legacy row changed after an earlier import.');
            }
            $this->increment($entity.'.already_recorded');
        }
        return $record;
    }

    private function record(string $entity, array $row, ?int $targetId, string $status): void
    {
        \App\Database\RelationalFields::insert('legacy_import_records', [
            'source' => self::SOURCE, 'entity' => $entity, 'legacy_id' => $row['id'], 'target_id' => $targetId,
            'status' => $status, 'payload' => Crypt::encryptString(serialize($row)), 'source_sha256' => $this->hash,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->increment($entity.'.'.$status);
    }

    private function increment(string $key): void
    {
        $this->stats[$key] = ($this->stats[$key] ?? 0) + 1;
    }

    private function existingCodes(string $table, string $column): array
    {
        return array_fill_keys(DB::table($table)->whereNotIn('id', DB::table('legacy_import_records')
            ->select('target_id')->where('source', self::SOURCE)->where('entity', $table)->whereNotNull('target_id'))
            ->whereNotNull($column)->pluck($column)->map(fn ($v) => strtolower(trim($v)))->all(), true);
    }

    private function noteRepeatedCode(string $type, ?string $code): void
    {
        if (!$code) {
            return;
        }
        $key = $type.':'.strtolower($code);
        if (isset($this->seenCodes[$key])) {
            $this->increment('source_repeated_'.$type.'_preserved');
        }
        $this->seenCodes[$key] = true;
    }
}
