<?php

namespace Tests\Feature;

use App\Services\CompanyIdentity;
use Tests\TestCase;

class CompanyIdentityTest extends TestCase
{
    public function test_provider_position_token_does_not_replace_client_position(): void
    {
        $html = '<p><strong>Fachry Hidayat</strong> sebagai Direktur Utama</p><table><tr><td>PIHAK PERTAMA</td><td>PIHAK KEDUA</td></tr><tr><td>[PROVIDER_DIRECTOR]</td><td>[FULLNAME]</td></tr><tr><td>Direktur Utama</td><td>Direktur Utama</td></tr></table>';
        $html = app(CompanyIdentity::class)->tokenize($html);
        $this->assertSame(2, substr_count($html, '[PROVIDER_POSITION]'));
        $this->assertSame(1, substr_count($html, 'Direktur Utama'));
        $this->assertStringContainsString('<td>Direktur Utama</td>', $html);
    }

    public function test_every_legacy_contract_template_uses_company_identity_tokens(): void
    {
        foreach (glob(resource_path('contract/*.html')) as $path) {
            $original = file_get_contents($path);
            if (!preg_match('/Digital\s+Masif/i', $original)) continue;
            $html = app(CompanyIdentity::class)->tokenize($original);
            $this->assertDoesNotMatchRegularExpression('/Digital\s+Masif|Fachry\s+Hidayat|Yunita\s+Afianti|Ciledug|(?:yunitasoundfresh|digitaldmsv)@gmail\.com|087735629645/i', $html, basename($path));
            $this->assertStringContainsString('[PROVIDER_NAME]', $html);
            $this->assertStringContainsString('[PROVIDER_ADDRESS]', $html);
            $this->assertStringContainsString('[PROVIDER_DIRECTOR]', $html);
            if (str_contains($original, 'No. Telp')) $this->assertStringContainsString('[PROVIDER_CONTACT_ADDRESS]', $html);
        }
    }
}
