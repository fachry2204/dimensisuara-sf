<?php
// Read-only integration regression against the configured local database.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$mode = $argv[1] ?? 'roles';
if ($mode === 'roles') {
    $data = $app->make(App\Http\Controllers\Admin\UserRolesController::class)->index()->getData();
    $source = file_get_contents(__DIR__.'/../resources/views/admin/user-roles/index.blade.php');
    preg_match('/<div class="roles-summary">.*?<\/div>\s*\n/', $source, $matches);
    if (empty($matches)) throw new RuntimeException('Summary markup missing');
    $html = Illuminate\Support\Facades\Blade::render($matches[0], $data);
    if (!str_contains($html, 'Total role')) throw new RuntimeException('Summary did not render');
    echo "PASS roles summary rendered\n";
} else {
    $request = Illuminate\Http\Request::create('/admin/logactivity/search', 'GET', [
        'draw'=>1, 'start'=>0, 'length'=>10,
        'order'=>[['column'=>3, 'dir'=>'desc']], 'search'=>['value'=>''],
    ]);
    $response = $app->make(App\Http\Controllers\Admin\LogActivityController::class)->get_data($request);
    $data = $response->getData(true);
    if ($response->getStatusCode() !== 200 || !isset($data['recordsTotal'], $data['recordsFiltered'], $data['data']) || count($data['data']) > 10) {
        throw new RuntimeException('Log pagination failed; HTTP '.$response->getStatusCode());
    }
    echo 'PASS log pagination: '.count($data['data']).' rows; peak '.round(memory_get_peak_usage(true)/1048576)." MB\n";
}
