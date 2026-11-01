<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\LogActivity;
use App\Models\Release;
use App\Models\Track;
use App\Models\User\Profile;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ActivityLogDetails
{
    private array $requestChanges = [];

    public const FIELDS = [
        'status'=>'Status','firstname'=>'Nama depan','lastname'=>'Nama belakang','name'=>'Nama',
        'companyname'=>'Nama perusahaan','is_company'=>'Akun perusahaan','title'=>'Judul',
        'recordlabel'=>'Nama label','upc'=>'UPC','isrc'=>'ISRC','releaseon'=>'Tanggal rilis',
        'releaseddate'=>'Tanggal rilis awal','tiktok_ytmusic_rerelease_date'=>'Tanggal pre-release',
        'version'=>'Versi','genre'=>'Genre','subgenre'=>'Subgenre','type'=>'Jenis',
        'feature_aggregator'=>'Aggregator','feature_publishing'=>'Publishing','feature_mcn'=>'MCN',
        'whitelabel_enabled'=>'Whitelabel','aggregator_rate'=>'Bagian Aggregator user (%)',
        'publishing_rate'=>'Bagian Publishing user (%)','mcn_rate'=>'Bagian MCN user (%)',
        'user_percentage'=>'Bagian user (%)','system_percentage'=>'Bagian system (%)',
        'contract_date'=>'Tanggal kontrak','docnum'=>'Nomor kontrak','reason'=>'Alasan',
        'email_verified_at'=>'Verifikasi email','phone'=>'Nomor telepon','address1'=>'Alamat',
        'address2'=>'Alamat tambahan','city'=>'Kota','state'=>'Provinsi','country'=>'Negara','zip'=>'Kode pos',
        'bankname'=>'Bank','accountname'=>'Pemilik rekening','accountnumber'=>'Nomor rekening',
        'ktp_num'=>'NIK','npwppersonal_num'=>'NPWP pribadi','npwpcompany_num'=>'NPWP perusahaan',
        'nibcompany_num'=>'NIB','position'=>'Jabatan',
    ];
    private const PRIVATE_FIELDS = ['phone','address1','address2','accountname','accountnumber','ktp_num','npwppersonal_num','npwpcompany_num','nibcompany_num'];

    public function snapshot(Model $model): array
    {
        $type = match (true) {
            $model instanceof Contract=>'Kontrak', $model instanceof Release=>'Rilis',
            $model instanceof Track=>'Lagu', $model instanceof Profile=>'Profil user', default=>'User',
        };
        $name = $model instanceof Profile ? trim(($model->firstname ?? '').' '.($model->lastname ?? ''))
            : ($model instanceof Contract ? $model->docnum : ($model->title ?? $model->name));
        $details = [];
        if ($model instanceof Contract) {
            $details[] = ['label'=>'Layanan','value'=>ucfirst($model->type ?? '')];
            if ($model->profileid) {
                $profile = $model->profile;
                if ($profile) $details[] = ['label'=>'Pemilik kontrak','value'=>trim(($profile->firstname ?? '').' '.($profile->lastname ?? '')) ?: 'Profil #'.$profile->id];
            }
            foreach (['contract_date','user_percentage','system_percentage'] as $field) {
                if ($model->$field !== null) $details[] = ['label'=>self::FIELDS[$field],'value'=>$this->value($field,$model->$field)];
            }
        }
        return ['type'=>$type,'id'=>$model->getKey(),'name'=>$name ?: $type.' #'.$model->getKey(),'details'=>$details];
    }

    public function changes(Model $model): array
    {
        $changes = [];
        foreach (array_intersect_key($model->getChanges(),self::FIELDS) as $field=>$value) {
            $private = in_array($field,self::PRIVATE_FIELDS,true);
            $changes[] = ['label'=>self::FIELDS[$field],
                'before'=>$private ? 'Disembunyikan' : $this->value($field,$model->getRawOriginal($field)),
                'after'=>$private ? 'Diperbarui (nilai disembunyikan)' : $this->value($field,$model->$field)];
        }
        return $changes;
    }

    public function remember(Model $model, array $subject, array $changes): void
    {
        $key = get_class($model).':'.$model->getKey();
        $merged = collect($this->requestChanges[$key]['audit_changes'] ?? [])->keyBy('label');
        foreach ($changes as $change) {
            if (isset($merged[$change['label']])) $change['before']=$merged[$change['label']]['before'];
            $merged[$change['label']]=$change;
        }
        $context = ['audit_subject'=>$subject,'audit_changes'=>$merged->values()->all()];
        $this->requestChanges[$key]=$context;
        if ($model instanceof Profile && $model->userid) $this->requestChanges[User::class.':'.$model->userid]=$context;
    }

    public function enrich(string $message, array $context): array
    {
        if (isset($context['audit_changes'])) return $context;
        $class = $context['model_type'] ?? null;
        $id = $context['model_id'] ?? null;
        if (!$class && preg_match('/^User ID (\d+)/',$message,$match)) { $class=User::class; $id=$match[1]; }
        return array_merge($context,$this->requestChanges[$class.':'.$id] ?? []);
    }

    private function value(string $field, mixed $value): string
    {
        if ($value === null || $value === '') return '—';
        if (str_starts_with($field,'feature_') || $field === 'whitelabel_enabled') return (bool) $value ? 'Aktif' : 'Nonaktif';
        if ($field === 'is_company') return (bool) $value ? 'Perusahaan' : 'Individu';
        if ($field === 'email_verified_at') return 'Sudah verifikasi';
        if ($value instanceof \DateTimeInterface) return $value->format('d-m-Y');
        if (in_array($field,['contract_date','releaseon','releaseddate','tiktok_ytmusic_rerelease_date'],true)) {
            try { return \Carbon\Carbon::parse($value)->format('d-m-Y'); } catch (\Throwable) { return (string) $value; }
        }
        if (is_array($value) || is_object($value)) return json_encode($value,JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        return (string) $value;
    }

    public function present(LogActivity $log): array
    {
        $context = is_array($log->context) ? $log->context : (json_decode($log->context ?? '',true) ?: []);
        $subject = $context['audit_subject'] ?? null;
        $current = false;
        if (!$subject) {
            // Whitelist supported models; never instantiate a class supplied by a log entry.
            $class = $context['model_type'] ?? null;
            $id = $context['model_id'] ?? null;
            if (!$class && preg_match('/^User ID (\d+)/',$log->message,$match)) { $class=User::class; $id=$match[1]; }
            $classes = [Contract::class,Release::class,Track::class,Profile::class,User::class];
            if ($id && in_array($class,$classes,true)) {
                $model = new $class;
                if (Schema::hasTable($model->getTable()) && ($record = $class::find($id))) {
                    $subject = $this->snapshot($record);
                    $current = true;
                }
            }
        }
        $table = $log->admin_user_id ? 'user_admin' : 'users';
        $actorId = $log->admin_user_id ?: $log->userid;
        $actor = $actorId && Schema::hasTable($table) ? DB::table($table)->where('id',$actorId)->value('name') : null;
        $summary = match ($log->message) {
            'Contract created by admin.'=>'Admin membuat kontrak.',
            'Contract deleted by admin.'=>'Admin menghapus kontrak.',
            'Contract uploaded by admin.'=>'Admin mengunggah dokumen kontrak.',
            default=>$log->message,
        };
        return ['summary'=>$summary,'actor'=>$actor ?: ($actorId ? 'Akun #'.$actorId : 'Sistem'),
            'subject'=>$subject,'changes'=>$context['audit_changes'] ?? [],
            'note'=>$current ? 'Informasi objek di bawah diambil dari data saat ini. Log lama tidak merekam nilai sebelum/sesudah perubahan.'
                : (empty($context['audit_changes']) ? 'Nilai sebelum/sesudah tidak tercatat pada log ini.' : null),
            'technical'=>$this->redact($context)];
    }

    private function redact(array $context): array
    {
        foreach ($context as $key=>$value) {
            if (preg_match('/password|token|secret|authorization|cookie|accountnumber|ktp_num|npwp|signature/i',(string) $key)) $context[$key]='Disembunyikan';
            elseif (is_array($value)) $context[$key]=$this->redact($value);
        }
        return $context;
    }
}
