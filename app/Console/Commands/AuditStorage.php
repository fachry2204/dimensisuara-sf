<?php

namespace App\Console\Commands;

use App\Database\RelationalFields;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditStorage extends Command
{
    protected $signature = 'system:audit-storage {--integrity : Check database foreign keys for orphaned rows}';
    protected $description = 'Read-only audit of relational persistence, legacy columns and database integrity';

    public function handle(): int
    {
        $issues = 0; $rows = [];
        foreach (['Sessions'=>config('session.driver'),'Cache'=>config('cache.default'),'Queue'=>config('queue.default')] as $name=>$driver) {
            $ok = $driver === 'database'; $issues += !$ok;
            $rows[] = [$name,$driver,$ok ? 'OK' : 'CHECK'];
        }
        $logging = config('logging.default');
        $databaseLogs = $logging === 'db' || ($logging === 'stack' && in_array('db',config('logging.channels.stack.channels',[]),true));
        $rows[] = ['Activity log',$logging,$databaseLogs ? 'OK' : 'CHECK']; $issues += !$databaseLogs;
        $failedDriver = config('queue.failed.driver');
        $rows[] = ['Failed jobs',$failedDriver,$failedDriver === 'database-uuids' ? 'OK' : 'CHECK'];
        $issues += $failedDriver !== 'database-uuids';
        foreach (['database','status-notifications','legacy-import','drive-archive'] as $connection) {
            $backend = config('queue.connections.'.$connection.'.driver');
            $queueDatabase = config('queue.connections.'.$connection.'.connection');
            if ($backend !== 'database' || ($queueDatabase && $queueDatabase !== config('database.default'))) {
                $issues++; $rows[] = ['Queue '.$connection,'Requires normalized application database','CHECK'];
            }
        }
        $this->table(['Operational state','Storage','Status'],$rows);

        $rows = [];
        foreach (RelationalFields::MAP as $owner=>$map) {
            if (!Schema::hasTable($owner)) { $issues++; $rows[] = [$owner,$map['table'],'Parent missing']; continue; }
            $legacy = array_filter(array_keys($map['fields']),fn ($field) => Schema::hasColumn($owner,$field));
            $ready = Schema::hasTable($map['table']);
            $status = !$ready ? 'Relations missing' : ($legacy ? 'Legacy columns: '.implode(', ',$legacy) : 'OK');
            if (!$ready || $legacy) $issues++;
            $rows[] = [$owner,$map['table'],$status];
        }
        $this->table(['Parent','Relational values','Status'],$rows);

        $nativeJson = [];
        foreach (Schema::getTables() as $table) {
            foreach (Schema::getColumns($table['name']) as $column) {
                if (in_array(strtolower($column['type_name']),['json','jsonb'],true)) $nativeJson[] = $table['name'].'.'.$column['name'];
            }
        }
        if ($nativeJson) { $issues += count($nativeJson); $this->error('Remaining JSON columns: '.implode(', ',$nativeJson)); }
        else $this->info('Native JSON columns: 0');

        if ($this->option('integrity')) {
            $orphans = [];
            foreach (Schema::getTables() as $table) {
                foreach (Schema::getForeignKeys($table['name']) as $foreign) {
                    $query = DB::table($table['name'].' as child')->leftJoin($foreign['foreign_table'].' as parent',function ($join) use ($foreign) {
                        foreach ($foreign['columns'] as $i=>$column) $join->on('child.'.$column,'=','parent.'.$foreign['foreign_columns'][$i]);
                    });
                    foreach ($foreign['columns'] as $column) $query->whereNotNull('child.'.$column);
                    $count = $query->whereNull('parent.'.$foreign['foreign_columns'][0])->count();
                    if ($count) { $issues++; $orphans[] = [$table['name'],implode(', ',$foreign['columns']),$count]; }
                }
            }
            // Imported databases may be missing declared FK constraints; verify business links explicitly, too.
            $links = [
                ['user_profiles','userid','users'],['releases','profileid','user_profiles'],['tracks','profileid','user_profiles'],
                ['transactions','profileid','user_profiles'],['transaction_items','profileid','user_profiles'],
                ['transaction_items','transactionid','transactions'],['settlements','profileid','user_profiles'],
                ['settlement_items','profileid','user_profiles'],['settlement_items','settlementid','settlements'],
                ['report_rows','profileid','user_profiles'],['report_rows','trackid','tracks'],
                ['report_rows','report_import_id','report_imports'],['publishings','profileid','user_profiles'],
                ['publishings','release_id','releases'],['publishings','track_id','tracks'],
                ['publishing_signees','publishingid','publishings'],['filestorage','profileid','user_profiles'],
            ];
            foreach ($links as [$table,$column,$parent]) {
                if (!Schema::hasColumn($table,$column) || !Schema::hasTable($parent)) continue;
                $alreadyChecked = collect(Schema::getForeignKeys($table))->contains(fn ($key) => $key['columns'] === [$column] && $key['foreign_table'] === $parent);
                if ($alreadyChecked) continue;
                $count = DB::table($table.' as child')->leftJoin($parent.' as parent','child.'.$column,'=','parent.id')->whereNotNull('child.'.$column)->whereNull('parent.id')->count();
                if ($count) { $issues++; $orphans[] = [$table,$column.' (not declared as FK)',$count]; }
            }
            foreach (RelationalFields::MAP as $owner=>$map) {
                if (!Schema::hasTable($map['table'])) continue;
                $invalidLinks = DB::table($map['table'].' as child')->join($map['table'].' as parent','child.parent_id','=','parent.id')
                    ->where(fn ($q) => $q->whereColumn('child.owner_id','!=','parent.owner_id')->orWhereColumn('child.field','!=','parent.field'))->count();
                if ($invalidLinks) { $issues++; $orphans[] = [$map['table'],'Parent belongs to another record/field',$invalidLinks]; }
                $invalidValues = 0;
                foreach (DB::table($map['table'])->whereNull('parent_id')->select('owner_id','field')->cursor() as $root) {
                    try { RelationalFields::read($owner,$root->owner_id,$root->field); }
                    catch (\Throwable) { $invalidValues++; }
                }
                if ($invalidValues) { $issues++; $orphans[] = [$map['table'],'Unreadable or invalid encrypted values',$invalidValues]; }
            }
            if ($orphans) $this->table(['Table','Orphaned relation','Rows'],$orphans);
            else $this->info('Foreign-key orphans: 0');
        }
        $this->line('This command does not change application data or configuration.');
        if ($issues) { $this->warn($issues.' findings require review.'); return self::FAILURE; }
        $this->info('Storage audit passed.');
        return self::SUCCESS;
    }
}
