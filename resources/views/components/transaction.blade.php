<div class="row g-3 justify-content-center">
    <div class="col-md-9">
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
                        @foreach ($transaction->items as $item)
                        <tr>
                            @if ($loop->first)
                                <td rowspan="{{ $transaction->items()->count() }}" class="text-center">{{ $transaction->profile->fullname }}</td>
                            @endif
                            <td>{{ DateTime::createFromFormat('!m', $item->month)->format('F') }} {{ $transaction->year }}</td>
                            <td class="text-end">{{ number_format($item->amount, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($item->amount, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="text-end fw-bold">
                        <tr>
                            <td colspan="3">Total Revenue</td>
                            <td>{{ number_format($transaction->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td colspan="3">Carried Over</td>
                            <td>{{ number_format($transaction->carryover, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td colspan="3">PPh 21</td>
                            <td>{{ number_format($transaction->tax, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td colspan="3">Grand Total Revenue</td>
                            <td>{{ number_format($transaction->total, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
                <table>
                    <tr>
                        <td class="text-muted">Account Number</td>
                        <td class="ps-3">{{ $transaction->profile->accountnumber }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Account Name</td>
                        <td class="ps-3">{{ $transaction->profile->accountname }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Bank Name</td>
                        <td class="ps-3">{{ $transaction->profile->bankname }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status</td>
                        <td class="ps-3">@include('components.badge-transaction', [ 'status' => $transaction->status ])</td>
                    </tr>
                </table>

                <div class="mt-3">
                    <ul>
                        @if ($transaction->slip)
                            <li>
                                <a href="{{ $transaction->slip_url }}" target="_blank">Download Transaction Slip</a>
                            </li>
                        @endif
                        @if ($transaction->tax_invoice)
                            <li>
                                <a href="{{ $transaction->tax_invoice_url }}" target="_blank">Download Payment Receipt</a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>