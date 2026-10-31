<?php

// Manual, read-only visual audit. HTML snapshots stay outside the public directory.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver' => 'array', 'app.url' => 'http://127.0.0.1:8000']);
Illuminate\Support\Facades\URL::forceRootUrl('http://127.0.0.1:8000');
$output = sys_get_temp_dir().'/dimensi-theme-audit';
Illuminate\Support\Facades\File::ensureDirectoryExists($output);
$db = Illuminate\Support\Facades\DB::connection();
$db->statement('SET TRANSACTION READ ONLY');
$db->beginTransaction();
try {
    $profile = App\Models\User\Profile::where('status', 'verified')->firstOrFail();
    $user = new class extends App\Models\User\User {
        public function hasPermission(string $permission): bool { return true; }
    };
    $user->forceFill(['id' => $profile->userid, 'name' => 'Theme Audit', 'email_verified_at' => now()]);
    $user->setRelation('profile', $profile);
    $user->setRelation('unreadNotifications', collect());
    $releaseId = App\Models\Release::where('profileid', $profile->id)->value('id');
    $publishingId = App\Models\Publishing\Publishing::value('id');
    $roleId = App\Models\Access\Role::value('id');
    $routes = [
        ['admin.home'], ['admin.releases.index'], ['admin.releases.create'],
        ['admin.releases.edit', ['id' => $releaseId]], ['admin.releases.show', ['id' => $releaseId]],
        ['admin.releases.reporting.index'], ['admin.releases.contract.index'], ['admin.releases.settlement.index'],
        ['admin.publishing.index'], ['admin.publishing.create'], ['admin.publishing.show', ['id' => $publishingId]],
        ['admin.publishing.edit', ['id' => $publishingId]], ['admin.publishing.contract.index'],
        ['admin.publishing.reporting.index'], ['admin.publishing.settlement.index'], ['admin.mcn.index'],
        ['admin.users.index'], ['admin.users.create'], ['admin.users.show', ['id' => $profile->userid]],
        ['admin.users.edit', ['id' => $profile->userid]], ['admin.user-roles.index'], ['admin.user-roles.create'],
        ['admin.user-roles.edit', ['id' => $roleId]], ['admin.broadcast.index'], ['admin.broadcast.create'],
        ['admin.announcement.index'], ['admin.announcement.create'], ['admin.logactivity.index'],
        ['admin.system-settings.index'], ['admin.automation.index'], ['notifications.index'], ['home'], ['profile'], ['analytics.index'], ['home.earnings'],
        ['settlements.index'], ['releases.index'], ['releases.create', ['type' => 'single']],
        ['releases.create', ['type' => 'album']], ['releases.show', ['id' => $releaseId]],
        ['publishing.index'], ['publishing.create'], ['mcn.index'], ['mcn.create'],
        ['login', [], 'guest'], ['register', [], 'guest'], ['password.request', [], 'guest'],
        ['password.reset', ['token' => 'audit-token'], 'guest'], ['register.tnc', [], 'guest'],
    ];
    $results = [];
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    foreach ($routes as $spec) {
        $name = $spec[0];
        $parameters = $spec[1] ?? [];
        if (in_array(null, $parameters, true)) continue;
        if (($spec[2] ?? '') === 'guest') Illuminate\Support\Facades\Auth::forgetGuards();
        else Illuminate\Support\Facades\Auth::guard()->setUser($user);
        $response = $kernel->handle(Illuminate\Http\Request::create(route($name, $parameters), 'GET'));
        $filename = str_replace('.', '-', $name).(isset($parameters['type']) ? '-'.$parameters['type'] : '').'.html';
        $html = $response->getContent();
        $html = preg_replace('#<script[^>]+src="https://app\.cloudchat\.id/[^>]*></script>#', '', $html);
        // Fixtures cannot submit or fetch data, and do not contain auth cookies.
        $html = str_replace('<head>', '<head><meta name="referrer" content="no-referrer"><meta http-equiv="Content-Security-Policy" content="connect-src \'none\'; form-action \'none\'; img-src \'self\' data: http://127.0.0.1:8000"><script>document.addEventListener("submit", event => event.preventDefault(), true);</script>', $html);
        file_put_contents($output.'/'.$filename, $html);
        $results[] = ['route' => $name, 'file' => $filename, 'status' => $response->getStatusCode()];
        echo $response->getStatusCode().' '.$name.PHP_EOL;
    }
    Illuminate\Support\Facades\Auth::guard()->setUser($user);
    $lab = <<<'BLADE'
@extends('layouts.app')
@section('title', 'Form Control Audit')
@section('content')
<div class="card"><div class="card-body p-4">
<h1>Form Control Audit</h1><p>Data uji lokal; tidak menyimpan perubahan.</p>
<form onsubmit="event.preventDefault()">
<div class="row g-3">
<div class="col-md-6"><label for="audit-text">Nama uji</label><input class="form-control" id="audit-text" name="audit_text" placeholder="Isi teks"></div>
<div class="col-md-6"><label for="audit-select">Pilihan uji</label><select id="audit-select" name="audit_select" class="form-select"><option value="a">Pilihan A</option><option value="b">Pilihan B</option></select></div>
<div class="col-md-6"><label for="audit-date">Tanggal uji</label><input class="form-control" id="audit-date" type="date"></div>
<div class="col-md-6"><label for="audit-file">File uji</label><input class="form-control" id="audit-file" type="file"></div>
<div class="col-md-6"><label for="audit-notes">Catatan uji</label><textarea class="form-control" name="audit_notes" id="audit-notes"></textarea></div>
<div class="col-md-6"><label for="audit-disabled">Tidak aktif</label><input class="form-control" id="audit-disabled" value="Tidak dapat diubah" disabled></div>
</div>
<fieldset class="my-3"><legend>Pilihan radio</legend>
<div class="form-check"><input class="form-check-input" type="radio" name="audit_radio" id="audit-radio-a" value="a" checked><label class="form-check-label" for="audit-radio-a">Radio A</label></div>
<div class="form-check"><input class="form-check-input" type="radio" name="audit_radio" id="audit-radio-b" value="b"><label class="form-check-label" for="audit-radio-b">Radio B</label></div>
</fieldset>
<div class="form-check"><input class="form-check-input" type="checkbox" name="audit_check" id="audit-check" value="1"><label class="form-check-label" for="audit-check">Checkbox uji</label></div>
<div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="audit_switch" id="audit-switch" value="1"><label class="form-check-label" for="audit-switch">Switch uji</label></div>
<div class="btn-group my-3"><input class="btn-check" type="radio" name="audit_mode" id="audit-mode-a" value="a" checked><label class="btn btn-outline-primary" for="audit-mode-a">Mode A</label><input class="btn-check" type="radio" name="audit_mode" id="audit-mode-b" value="b"><label class="btn btn-outline-primary" for="audit-mode-b">Mode B</label></div>
<div class="my-3 d-flex gap-2"><button type="button" class="btn btn-primary">Primary</button><button type="button" class="btn btn-success">Success</button><button type="button" class="btn btn-danger">Danger</button><button type="button" class="btn btn-outline-primary">Outline</button></div>
<div class="d-flex gap-2"><button type="button" class="btn release-next-button">Next</button><button type="button" class="btn release-save-draft">Save Draft</button></div>
<output id="audit-values" class="d-block mt-3" aria-live="polite"></output>
</form>
</div></div>
<script>document.querySelector('form').addEventListener('change', function () { document.getElementById('audit-values').textContent = JSON.stringify(Object.fromEntries(new FormData(this))); });</script>
@endsection
BLADE;
    $labHtml = Illuminate\Support\Facades\Blade::render($lab);
    $labHtml = preg_replace('#<script[^>]+src="https://app\.cloudchat\.id/[^>]*></script>#', '', $labHtml);
    file_put_contents($output.'/controls.html', $labHtml);
    file_put_contents($output.'/results.json', json_encode($results, JSON_PRETTY_PRINT));
    $links = '<h1>Theme Audit</h1><a href="controls.html">Kontrol form</a><ul>';
    foreach ($results as $result) $links .= '<li><a href="'.e($result['file']).'">'.e($result['route']).'</a> '.$result['status'].'</li>';
    file_put_contents($output.'/index.html', $links.'</ul>');
    echo 'Snapshots: '.$output.PHP_EOL;
} finally {
    $db->rollBack();
}
