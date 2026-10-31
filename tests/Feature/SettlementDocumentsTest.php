<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\Settlement\Settlement;
use Tests\TestCase;

class SettlementDocumentsTest extends TestCase
{
    private function document(int $id, string $name): File
    {
        $file = new File(['disk'=>'local', 'original_name'=>$name]);
        $file->forceFill(['id'=>$id, 'updated_at'=>'2026-10-30 10:00:00']);
        return $file;
    }

    public function test_bank_and_tax_evidence_have_distinct_icon_buttons_and_correct_files(): void
    {
        $settlement = new Settlement();
        $bank = $this->document(101, 'bank-transfer.pdf');
        $tax = $this->document(102, 'bukti-pajak.pdf');
        $settlement->setRelation('slip', $bank);
        $settlement->setRelation('tax_invoice', $tax);
        $html = view('components.settlement-documents', compact('settlement'))->render();
        $this->assertStringContainsString('account_balance', $html);
        $this->assertStringContainsString('receipt_long', $html);
        $this->assertStringContainsString('Unduh Bukti Transfer Bank', $html);
        $this->assertStringContainsString('Unduh Bukti Pajak', $html);
        $dom = new \DOMDocument();
        $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $links = $dom->getElementsByTagName('a');
        $this->assertSame(2, $links->length);
        $this->assertSame($bank->url, $links->item(0)->getAttribute('href'));
        $this->assertSame($tax->url, $links->item(1)->getAttribute('href'));
        $this->assertStringContainsString('btn', $links->item(0)->getAttribute('class'));
    }

    public function test_missing_bank_evidence_remains_visible_without_using_tax_file_as_bank_proof(): void
    {
        $settlement = new Settlement();
        $settlement->setRelation('slip', null);
        $settlement->setRelation('tax_invoice', $this->document(102, 'pajak.pdf'));
        $html = view('components.settlement-documents', compact('settlement'))->render();
        $this->assertStringContainsString('Bukti Transfer Bank', $html);
        $this->assertStringContainsString('Belum diupload', $html);
        $this->assertStringNotContainsString('Unduh Bukti Transfer Bank', $html);
        $this->assertStringContainsString('Unduh Bukti Pajak', $html);
    }
}
