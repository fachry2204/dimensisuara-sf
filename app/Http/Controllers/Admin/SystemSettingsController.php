<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SystemSettingsController extends Controller
{
    public function index(SystemSettings $settings)
    {
        $templates = app(\App\Services\AggregatorContractTemplates::class);
        $driveReady = \Illuminate\Support\Facades\Schema::hasTable('drive_transfers');
        $notificationTemplates = app(\App\Services\StatusNotificationTemplates::class);
        $notificationEvents = $notificationTemplates->events();
        $requestedEvent = request('notification_event','release.released');
        $notificationEvent = is_string($requestedEvent) && isset($notificationEvents[$requestedEvent]) ? $requestedEvent : 'release.released';
        $notificationsReady = \Illuminate\Support\Facades\Schema::hasTable('status_notification_deliveries');
        $importsReady = \App\Models\LegacyImportRun::schemaReady();
        $importRuns = $importsReady ? \App\Models\LegacyImportRun::latest('id')->limit(20)->get() : collect();
        $selectedImport = $importsReady && request('import') ? \App\Models\LegacyImportRun::find(request('import')) : $importRuns->first();
        return view('admin.system-settings.index', [
            'notificationEvents'=>$notificationEvents,'notificationEvent'=>$notificationEvent,'notificationsReady'=>$notificationsReady,
            'notificationConfigurations'=>['email'=>$notificationTemplates->configuration('email'),'whatsapp'=>$notificationTemplates->configuration('whatsapp')],
            'notificationHistory'=>$notificationsReady ? \App\Models\StatusNotificationDelivery::select(['id','event','channel','user_id','status','attempts','error','sent_at','updated_at'])->latest('id')->limit(40)->get()->groupBy('channel') : collect(),
            'driveReady'=>$driveReady,
            'driveStats'=>$driveReady ? \Illuminate\Support\Facades\DB::table('drive_transfers')->selectRaw('status, COUNT(*) AS total, SUM(size) AS bytes')->groupBy('status')->get()->keyBy('status') : collect(),
            'driveHistory'=>$driveReady ? \Illuminate\Support\Facades\DB::table('drive_transfers')->orderByDesc('updated_at')->limit(30)->get() : collect(),
            'importsReady' => $importsReady, 'importRuns' => $importRuns, 'selectedImport' => $selectedImport,
            'importMediaCounts' => $selectedImport ? $selectedImport->mediaResults()->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status') : collect(),
            'importMediaIssues' => $selectedImport ? $selectedImport->mediaResults()->where('status', 'issue')->orderBy('id')->paginate(25, ['*'], 'issues_page')->withQueryString() : null,
            'missingBranding' => collect(['logo'=>'Logo', 'favicon'=>'Favicon'])->filter(fn ($label, $key) => $settings->get($key) && !app(\App\Services\BrandingAssets::class)->path($settings->get($key)))->values()->all(),
            'settings' => $settings->publicValues(),
            'contractDrafts' => ['personal' => $templates->get(false), 'company' => $templates->get(true)],
            'hasCompanySignature' => app(\App\Services\CompanyIdentity::class)->signaturePath() !== null,
            'maintenanceRoles' => \App\Models\Access\Role::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, SystemSettings $settings)
    {
        $section = $request->input('section');
        if ($section === 'contract_prefix' && is_string($request->input('aggregator_contract_prefix'))) {
            $request->merge(['aggregator_contract_prefix' => strtoupper(trim($request->input('aggregator_contract_prefix')))]);
        }
        if ($section === 'aggregators' && $request->has('aggregator_names')) {
            $names = $request->input('aggregator_names') ?? '';
            if (is_string($names)) {
                $names = preg_split('/\R/u', $names);
            }
            if (is_array($names)) {
                $names = array_map(fn ($name) => is_string($name) ? trim($name) : $name, $names);
                $names = array_values(array_filter($names, fn ($name) => $name !== ''));
            }
            $request->merge(['aggregator_names' => $names]);
        }
        $rules = match ($section) {
            'contract_prefix' => [
                'aggregator_contract_prefix' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9][A-Z0-9._-]*$/'],
            ],
            'maintenance' => [
                'maintenance_enabled' => ['nullable','boolean'],
                'maintenance_reason' => ['required','string','max:3000'],
                'maintenance_roles' => ['nullable','array'],
                'maintenance_roles.*' => ['integer','distinct','exists:access_roles,id'],
                'maintenance_customers' => ['nullable','boolean'],
                'maintenance_guests' => ['nullable','boolean'],
            ],
            'company' => [
                'company_name' => ['required', 'string', 'max:200'],
                'company_address' => ['required', 'string', 'max:2000'],
                'company_director' => ['required', 'string', 'max:200'],
                'company_position' => ['required', 'string', 'max:200'],
                'company_signature' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096'],
                'remove_company_signature' => ['nullable', 'boolean'],
                'company_contact_name' => ['required', 'string', 'max:200'],
                'company_contact_address' => ['required', 'string', 'max:2000'],
                'company_phone' => ['required', 'string', 'max:30', 'regex:/^(?=(?:\D*\d){6})\+?[0-9() .-]{6,30}$/'],
                'company_email' => ['required', 'email', 'max:255'],
            ],
            'colors' => [
                'primary_color_mode' => ['required', Rule::in(['solid', 'gradient'])],
                'primary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'primary_gradient_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'primary_gradient_angle' => ['required', 'integer', 'between:0,360'],
            ],
            'contracts' => [
                'aggregator_draft_personal' => ['required', 'string', 'max:250000'],
                'aggregator_draft_company' => ['required', 'string', 'max:250000'],
            ],
            'aggregators' => [
                'aggregator_names' => ['present', 'array', 'max:100'],
                'aggregator_names.*' => ['required', 'string', 'max:100', 'distinct:ignore_case'],
            ],
            'branding' => [
                'app_name' => ['required', 'string', 'max:100'],
                'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096'],
                'favicon' => ['nullable', 'file', 'mimes:png,ico', 'max:512'],
                'remove_logo' => ['nullable', 'boolean'],
                'remove_favicon' => ['nullable', 'boolean'],
            ],
            'smtp' => [
                'smtp_enabled' => ['nullable', 'boolean'],
                'smtp_host' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9.-]+$/'],
                'smtp_port' => ['required', 'integer', 'between:1,65535'],
                'smtp_encryption' => ['required', Rule::in(['tls', 'ssl'])],
                'smtp_username' => ['nullable', 'required_if:smtp_enabled,1', 'email', 'max:255'],
                'smtp_password' => ['nullable', 'string', 'max:255'],
                'smtp_from_address' => ['nullable', 'required_if:smtp_enabled,1', 'email', 'max:255'],
                'smtp_from_name' => ['nullable', 'string', 'max:100'],
            ],
            'whatsapp' => [
                'whatsapp_url' => ['nullable', Rule::in(['https://api.fonnte.com', 'https://api.fonnte.com/'])],
                'whatsapp_number' => ['nullable', 'string', 'regex:/^\+?[0-9]{8,20}$/'],
                'whatsapp_token' => ['nullable', 'string', 'max:1024', 'regex:/^[^\r\n]+$/'],
            ],
            default => abort(422, 'Bagian pengaturan tidak valid.'),
        };
        $validator = Validator::make($request->all(), $rules);
        $validator->after(function ($validator) use ($request, $section, $settings) {
            if ($section === 'smtp' && $request->boolean('smtp_enabled') && !$request->filled('smtp_password') && !$settings->get('smtp_password')) {
                $validator->errors()->add('smtp_password', 'Isi App Password Gmail untuk mengaktifkan SMTP.');
            }
            if ($section === 'whatsapp' && !$request->filled('whatsapp_token') && !$settings->fonnteToken()) {
                $validator->errors()->add('whatsapp_token', 'Isi token device Fonnte.');
            }
            if ($section === 'maintenance' && $request->boolean('maintenance_enabled') && !$request->input('maintenance_roles') && !$request->boolean('maintenance_customers') && !$request->boolean('maintenance_guests')) {
                $validator->errors()->add('maintenance_roles', 'Pilih minimal satu kelompok pengguna untuk mode maintenance.');
            }
        });
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($request->except('smtp_password', 'whatsapp_token'));
        }
        $values = $validator->validated();
        if ($section === 'whatsapp') {
            $values['whatsapp_url'] = 'https://api.fonnte.com';
            $values['whatsapp_provider'] = 'fonnte';
        }
        if ($section === 'maintenance') {
            foreach (['maintenance_enabled','maintenance_customers','maintenance_guests'] as $key) $values[$key] = $request->boolean($key);
            $values['maintenance_roles'] = array_map('intval', $request->input('maintenance_roles', []));
        }
        if ($section === 'contracts') {
            foreach ($values as $key => $html) {
                $values[$key] = app(\App\Services\AggregatorContractTemplates::class)->validateDraft($html, $key);
            }
        }
        $newFiles = [];
        $oldFiles = [];
        $newSignaturePath = $oldSignaturePath = null;
        if ($section === 'company') {
            unset($values['company_signature'], $values['remove_company_signature']);
            if ($request->hasFile('company_signature') || $request->boolean('remove_company_signature')) {
                $oldFilename = $settings->get('company_signature');
                if (is_string($oldFilename) && preg_match('/^director-[a-f0-9-]+\.(png|jpg|jpeg|webp)$/', $oldFilename)) {
                    $oldSignaturePath = config('system-settings.company_signatures_path').DIRECTORY_SEPARATOR.$oldFilename;
                }
                $values['company_signature'] = '';
                if ($request->hasFile('company_signature')) {
                    File::ensureDirectoryExists(config('system-settings.company_signatures_path'), 0750);
                    $filename = 'director-'.\Illuminate\Support\Str::uuid().'.png';
                    $newSignaturePath = config('system-settings.company_signatures_path').DIRECTORY_SEPARATOR.$filename;
                    $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
                    $manager->read($request->file('company_signature')->getRealPath())->toPng()->save($newSignaturePath);
                    $values['company_signature'] = $filename;
                }
            }
        }
        if ($section === 'branding') {
            try {
                foreach (['logo', 'favicon'] as $key) {
                    unset($values[$key], $values['remove_'.$key]);
                    if ($request->hasFile($key)) {
                        $values[$key] = app(\App\Services\BrandingAssets::class)->store($key, $request->file($key));
                        $newFiles[] = $values[$key];
                        $oldFiles[] = $settings->get($key);
                    } elseif ($request->boolean('remove_'.$key)) {
                        $values[$key] = null;
                        $oldFiles[] = $settings->get($key);
                    }
                }
            } catch (\Throwable $exception) {
                foreach ($newFiles as $path) app(\App\Services\BrandingAssets::class)->delete($path);
                report($exception);
                return back()->withErrors(['branding'=>'Gambar gagal disimpan. Pastikan folder storage aplikasi dapat ditulis, lalu unggah ulang.'])->withInput($request->only('app_name', 'section'));
            }
        }
        if ($section === 'smtp') {
            $values['smtp_enabled'] = $request->boolean('smtp_enabled');
        }
        foreach (['smtp_password', 'whatsapp_token'] as $key) {
            if (empty($values[$key])) {
                unset($values[$key]);
            }
        }
        if (isset($values['whatsapp_url'])) {
            $values['whatsapp_url'] = rtrim($values['whatsapp_url'], '/');
        }
        try {
            $settings->save($values);
        } catch (\Throwable $exception) {
            if ($newSignaturePath) File::delete($newSignaturePath);
            foreach ($newFiles as $path) {
                app(\App\Services\BrandingAssets::class)->delete($path);
            }
            throw $exception;
        }
        if ($oldSignaturePath) File::delete($oldSignaturePath);
        foreach ($oldFiles as $path) {
            app(\App\Services\BrandingAssets::class)->delete($path);
        }

        $tab = $section === 'contract_prefix' ? 'contracts' : $section;
        return redirect()->route('admin.system-settings.index', in_array($tab, ['contracts', 'company', 'maintenance'], true) ? ['tab' => $tab] : [])->with('success', 'Pengaturan sistem berhasil disimpan.');
    }

    public function companySignature()
    {
        $path = app(\App\Services\CompanyIdentity::class)->signaturePath();
        abort_unless($path, 404);
        return response()->file($path, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'])->setPrivate();
    }
}
