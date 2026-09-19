@extends('layouts.app')
@section('title')
View Transaction
@endsection
@section('header')
    <div class="d-flex flex-row align-items-center justify-content-between mb-5">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('transactions.index') }}">My Transactions</a></li>
                    <li class="breadcrumb-item active" aria-current="page"></li>
                </ol>
            </nav>
            <h4 class="mb-0 font-weight-bold fs-2">View Transaction</h4>
        </div>
    </div>
@endsection
@section('content')
    @include('components.transaction', ['transaction' => $transaction])
@endsection