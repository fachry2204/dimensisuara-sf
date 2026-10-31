<div class="settlement-documents" aria-label="Dokumen transaksi">
    @foreach([
        ['title'=>'Bukti Transfer Bank', 'icon'=>'account_balance', 'file'=>$settlement->slip, 'url'=>$settlement->slip_url],
        ['title'=>'Bukti Pajak', 'icon'=>'receipt_long', 'file'=>$settlement->tax_invoice, 'url'=>$settlement->tax_invoice_url],
    ] as $document)
        <div class="settlement-document">
            <div class="settlement-document-heading"><span class="material-icons-outlined" aria-hidden="true">{{ $document['icon'] }}</span><strong>{{ $document['title'] }}</strong></div>
            @if($document['file'] && $document['url'])
                <a class="btn btn-outline-primary" href="{{ $document['url'] }}" target="_blank" rel="noopener"><span class="material-icons-outlined" aria-hidden="true">download</span>Unduh {{ $document['title'] }}</a>
                <small class="text-secondary">{{ $document['file']->original_name }}</small>
            @else
                <button type="button" class="btn btn-outline-secondary" disabled><span class="material-icons-outlined" aria-hidden="true">file_upload</span>{{ $document['file'] ? 'Dokumen belum dapat diunduh' : 'Belum diupload' }}</button>
            @endif
        </div>
    @endforeach
</div>
