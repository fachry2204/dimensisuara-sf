@extends('layouts.app')
@section('title')
Review Generated Transactions
@endsection
@section('header')
    <div class="d-flex flex-row align-items-center justify-content-between mb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.releases.settlement.index') }}">Aggregator Transactions</a></li>
                    <li class="breadcrumb-item active" aria-current="page"></li>
                </ol>
            </nav>
            <h4 class="mb-0 font-weight-bold fs-2">Review Generated Transactions</h4>
        </div>
    </div>
@endsection
@section('content')

    <div class="row">
        <div class="col-md-6">
            <table class="table table-borderless table-sm">
                <thead>
                    <tr>
                        <th>Year</th>
                        <th>Quarter</th>
                        <th>Include Carry Over</th>
                        <th>Allow Empty Transaction</th>
                        <th>Minimum Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $year }}</td>
                        <td>Q{{ $quarter }}</td>
                        <td>{{ $include_carryover ? 'Yes' : 'No' }}</td>
                        <td>{{ $allow_empty ? 'Yes' : 'No' }}</td>
                        <td>Rp{{ number_format($minimum, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="alert alert-warning fade show">
        <div class="d-flex align-items-center">
            <div class="ms-3">
                <h6 class="mb-0 text-warning">Warning!</h6>
                <div>The amount might be different from the calculation in the excel sheet due to rounding. Please check it manually.</div>
            </div>
        </div>
    </div>

    <table class="table table-striped">
        <thead>
            <th>User Name</th>
            <th>Bank Detail</th>
            <th>Revenue</th>
            <th>Carry Over</th>
            <th>Subtotal</th>
            <th>Tax</th>
            <th>Total</th>
            <th>Status</th>
        </thead>
        <tbody>
            @foreach($settlements as $settlement)
            <tr>
                <td>{{ $settlement['recipient']->fullname }}</td>
                <td>
                    <div>{{ $settlement['recipient']->bankname }}</div>
                    <div class="text-sm text-muted">{{ $settlement['recipient']->accountnumber }}</div>
                </td>
                <td>Rp{{ number_format($settlement['revenue'], 0, ',', '.') }}</td>
                <td>Rp{{ number_format($settlement['carryover'], 0, ',', '.') }}</td>
                <td>Rp{{ number_format($settlement['subtotal'], 0, ',', '.') }}</td>
                <td>@if($settlement['tax'] < 0) -@endif Rp{{ number_format(abs($settlement['tax']), 0, ',', '.') }}</td>
                <td>Rp{{ number_format($settlement['total'], 0, ',', '.') }}</td>
                <td>@include('components.badge-transaction', [ 'status' => $settlement['status'] ])</td>
            </tr>
            @endforeach
            <tr>
                <td colspan="2" class="text-end"><b>Total</b></td>
                <td><b>Rp{{ number_format( round($settlements->sum('revenue'),0) , 0 , ',' , '.' ) }}</b></td>
                <td><b>Rp{{ number_format( round($settlements->sum('carryover'),0) , 0 , ',' , '.' ) }}</b></td>
                <td><b>Rp{{ number_format( round($settlements->sum('subtotal'),0) , 0 , ',' , '.' ) }}</b></td>
                <td><b>Rp{{ number_format( round(abs($settlements->sum('tax')),0) , 0 , ',' , '.' ) }}</b></td>
                <td><b>Rp{{ number_format( round($settlements->sum('total'),0) , 0 , ',' , '.' ) }}</b></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <form action="{{ route('admin.releases.settlement.store') }}" method="POST">
        @csrf
        <div class="d-flex justify-content-center">
            <button type="submit" class="btn btn-primary">Finalize Transactions</button>
        </div>
    </form>
@endsection
