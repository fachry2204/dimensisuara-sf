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
    <div class="alert alert-warning fade show">
        <div class="d-flex align-items-center">
            <div class="ms-3">
                <h6 class="mb-0 text-warning">Warning!</h6>
                <div>The amount might be different from the calculation in the excel sheet due to rounding. Please check it manually.</div>
            </div>
        </div>
    </div>

    {{-- <div class="row justify-content-center">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card rounded-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div class="wh-48 d-flex bg-info text-info bg-opacity-10 align-items-center justify-content-center rounded-circle">
                            <span class="material-icons-outlined">payments</span>
                        </div>
                        <div class="">
                            <h5 class="mb-0">Rp{{ number_format( round($total,0) , 0 , ',' , '.' ) }}</h5>
                            <p class="mb-0">Total Client Revenue</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card rounded-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div class="wh-48 d-flex bg-success text-success bg-opacity-10 align-items-center justify-content-center rounded-circle">
                            <span class="material-icons-outlined">check_circle</span>
                        </div>
                        <div class="">
                            <h5 class="mb-0">Rp{{ number_format( round($paid,0) , 0 , ',' , '.' ) }}</h5>
                            <p class="mb-0">Total Paid Amount</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card rounded-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div class="wh-48 d-flex bg-warning text-warning bg-opacity-10 align-items-center justify-content-center rounded-circle">
                            <span class="material-icons-outlined">schedule</span>
                        </div>
                        <div class="">
                            <h5 class="mb-0">Rp{{ number_format( round($pending,0) , 0 , ',' , '.' ) }}</h5>
                            <p class="mb-0">Total Unpaid Amount</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div> --}}

    <table class="table table-striped">
        <thead>
            <th>User Name</th>
            <th>Bank Name</th>
            <th>Account Number</th>
            <th>Subtotal</th>
            <th>Carried Over</th>
            <th>Tax</th>
            <th>Total</th>
            <th>Status</th>
        </thead>
        <tbody>
            @foreach($transactions as $transaction)
            <tr>
                <td>{{ $transaction['profile']->fullname }}</td>
                <td>{{ $transaction['profile']->bankname }}</td>
                <td>{{ $transaction['profile']->accountnumber }}</td>
                <td>Rp{{ number_format($transaction['subtotal'], 0, ',', '.') }}</td>
                <td>Rp{{ number_format($transaction['carryover'], 0, ',', '.') }}</td>
                <td>Rp{{ number_format($transaction['tax'], 0, ',', '.') }}</td>
                <td>Rp{{ number_format($transaction['total'], 0, ',', '.') }}</td>
                <td>@include('components.badge-transaction', [ 'status' => $transaction['status'] ])</td>
            </tr>
            @endforeach
            <tr>
                <td colspan="3" class="text-end"><b>Total</b></td>
                <td><b>Rp{{ number_format( round($transactions->sum('subtotal'),0) , 0 , ',' , '.' ) }}</b></td>
                <td><b>Rp{{ number_format( round($transactions->sum('carryover'),0) , 0 , ',' , '.' ) }}</b></td>
                <td><b>Rp{{ number_format( round($transactions->sum('tax'),0) , 0 , ',' , '.' ) }}</b></td>
                <td><b>Rp{{ number_format( round($transactions->sum('total'),0) , 0 , ',' , '.' ) }}</b></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <form action="{{ route('admin.releases.settlement.store') }}" method="POST">
        @csrf
        <input type="hidden" name="quarter" value="{{ $quarter }}">
        <input type="hidden" name="year" value="{{ $year }}">
        <div class="d-flex justify-content-center">
            <button type="submit" class="btn btn-primary">Finalize Transactions</button>
        </div>
    </form>
@endsection