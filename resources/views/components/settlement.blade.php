<div class="card">
    <div class="card-body" style="overflow-x: auto">
        <h5 class="fw-bold mb-4 mt-3 text-center">Transaction Detail</h5>
        <table class="table table-bordered">
            <thead class="text-center">
                <tr>
                    <th colspan="3">Description</th>
                    <th rowspan="2">Total</th>
                </tr>
                <tr>
                    <th>NAME</th>
                    <th>REVENUE</th>
                    <th>SHARE ARTIS</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($settlement->items->where('type', 'revenue') as $item)
                <tr>
                    @if ($loop->first)
                        <td rowspan="{{ $settlement->items()->count() }}" class="text-center">{{ $settlement->profile->fullname }}</td>
                    @endif
                    <td>{{ $item->description }}</td>
                    <td class="text-end">{{ number_format($item->amount, 0, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($item->amount, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="text-end fw-bold">
                <tr>
                    <td colspan="3">Total Revenue</td>
                    <td>{{ number_format($settlement->items->where('type', 'revenue')->sum('amount'), 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="3">Carried Over</td>
                    <td>{{ number_format($settlement->items->where('type', 'carried_over')->sum('amount'), 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="3">PPh 21</td>
                    <td>{{ number_format($settlement->tax, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="3">Grand Total Revenue</td>
                    <td>{{ number_format($settlement->total, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
        <table>
            <tr>
                <td class="text-muted">Account Number</td>
                <td class="ps-3">{{ $settlement->profile->accountnumber }}</td>
            </tr>
            <tr>
                <td class="text-muted">Account Name</td>
                <td class="ps-3">{{ $settlement->profile->accountname }}</td>
            </tr>
            <tr>
                <td class="text-muted">Bank Name</td>
                <td class="ps-3">{{ $settlement->profile->bankname }}</td>
            </tr>
            <tr>
                <td class="text-muted">Status</td>
                <td class="ps-3">@include('components.badge-settlement', [ 'status' => $settlement->status ])</td>
            </tr>
        </table>

        <div class="mt-3">
            <ul>
                @if ($settlement->slip)
                    <li>
                        <a href="{{ $settlement->slip_url }}" target="_blank">Download Transaction Slip</a>
                    </li>
                @endif
                @if ($settlement->tax_invoice)
                    <li>
                        <a href="{{ $settlement->tax_invoice_url }}" target="_blank">Download Payment Receipt</a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</div>