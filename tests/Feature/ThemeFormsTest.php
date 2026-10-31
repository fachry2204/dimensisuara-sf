<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ThemeFormsTest extends TestCase
{
    private function checkedValues(string $view, string $field, array $data = []): array
    {
        app('request')->setLaravelSession(app('session.store'));
        $lines = file(resource_path('views/'.$view.'.blade.php'));
        $markup = '';
        for ($i = 0; $i < count($lines); $i++) {
            if (!str_contains($lines[$i], '<input') || !str_contains($lines[$i], 'name="'.$field.'"')) continue;
            $input = $lines[$i];
            while (!str_ends_with(trim($input), '>') && isset($lines[$i + 1])) $input .= $lines[++$i];
            $markup .= $input;
        }
        $this->assertNotSame('', $markup);
        $document = new \DOMDocument();
        $document->loadHTML('<html><body>'.Blade::render($markup, $data).'</body></html>', LIBXML_NOERROR | LIBXML_NOWARNING);
        $checked = [];
        foreach ($document->getElementsByTagName('input') as $input) {
            if ($input->hasAttribute('checked')) $checked[] = $input->getAttribute('value');
        }
        return $checked;
    }

    public function test_publishing_radio_restores_nonexclusive_after_validation_failure(): void
    {
        session()->flashInput(['sync_license_type' => 'non-exclusive']);
        $this->assertSame(['non-exclusive'], $this->checkedValues('admin/publishing/create', 'sync_license_type', ['publishing' => (object) ['sync_license_type' => null]]));
    }

    public function test_new_publishing_has_exactly_one_default_license(): void
    {
        $this->assertSame(['exclusive'], $this->checkedValues('admin/publishing/create', 'sync_license_type', ['publishing' => (object) ['sync_license_type' => null]]));
    }

    public function test_broadcast_restores_include_mode_after_validation_failure(): void
    {
        session()->flashInput(['selection_mode' => 'include']);
        $this->assertSame(['include'], $this->checkedValues('admin/broadcast/create', 'selection_mode'));
    }

    public function test_new_release_has_a_selected_default_even_before_alpine_initializes(): void
    {
        foreach (['admin/aggregator/create', 'customer/releases/create'] as $view) {
            $this->assertSame(['0'], $this->checkedValues($view, 'released', ['release' => (object) ['released' => null]]));
        }
    }

    public function test_release_radios_restore_the_submitted_value_in_both_editors(): void
    {
        session()->flashInput(['released' => '1']);
        foreach (['admin/aggregator/create', 'customer/releases/create'] as $view) {
            $this->assertSame(['1'], $this->checkedValues($view, 'released', ['release' => (object) ['released' => 0]]));
        }
    }
}
