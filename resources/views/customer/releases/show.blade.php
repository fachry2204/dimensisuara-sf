@extends('layouts.app')
@section('title')
View Release
@endsection
@section('header')
    <div class="customer-release-header d-flex flex-row align-items-center justify-content-between">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('releases.index') }}">My Releases</a></li>
                    <li class="breadcrumb-item active" aria-current="page"></li>
                </ol>
            </nav>
            <h4 class="mb-0 font-weight-bold fs-2">View Release</h4>
        </div>
    </div>
    <div class="mb-4"></div>
@endsection
@push('css')
<style>
.customer-release-header .breadcrumb-item a{color:#75b7ff}.customer-release-header .breadcrumb-item.active{color:#aec8e0}.customer-release-header h4{color:#f7fbff;letter-spacing:-.035em}.customer-release-detail{max-width:1240px;margin:0 auto}.customer-release-detail .release-overview-panel,.customer-release-detail .release-track-panel{position:relative;overflow:hidden;margin-bottom:24px;padding:28px;border:1px solid #285176;border-radius:15px;background:linear-gradient(135deg,rgba(15,36,61,.98),rgba(8,27,47,.98));box-shadow:0 18px 38px rgba(0,0,0,.15)}.customer-release-detail .release-overview-panel:after{position:absolute;right:48px;top:25px;color:rgba(105,174,231,.11);content:"♫  ♪";font-size:72px;font-weight:700;letter-spacing:24px}.customer-release-detail .release-overview-panel>.row{position:relative;z-index:1}.customer-release-detail .album-cover{display:block;width:100%;max-width:330px;height:auto;aspect-ratio:1 / 1;object-fit:contain;margin:0!important;border:1px solid #3b6a92;border-radius:10px;box-shadow:0 14px 28px rgba(0,0,0,.25)}.customer-release-detail .release-cover-column{padding:6px 8px}.customer-release-detail .release-cover-download{display:inline-flex;align-items:center;justify-content:center;gap:7px;width:100%;max-width:330px;margin-top:16px;border:1px solid #4c94ca;border-radius:8px;background:transparent;color:#b9d9f8;font-size:.8rem;font-weight:800}.customer-release-detail .release-metadata-column{padding:8px 18px}.customer-release-detail .album-title-status h2{color:#f7fbff;font-size:clamp(1.7rem,3vw,2.55rem);letter-spacing:-.04em}.customer-release-detail .album-artist{color:#8dbdff;font-size:1.16rem;font-weight:600}.customer-release-detail .album-status .badge{padding:7px 12px;border-radius:999px;font-size:.75rem}.customer-release-detail .release-metadata-grid{margin-top:18px}.customer-release-detail .release-items-table{width:100%;margin:0}.customer-release-detail .release-items-table td{padding:9px 8px;border:0}.customer-release-detail .release-items-table td:first-child{width:48%;color:#91b1d2!important;font-size:.78rem;font-weight:600}.customer-release-detail .release-items-table td:last-child{color:#f3f8ff;font-size:.88rem;font-weight:700;overflow-wrap:anywhere}.customer-release-detail .release-artists-table{background:rgba(8,25,43,.38);border:1px solid rgba(61,111,153,.52);border-radius:10px;padding:8px 12px}.customer-release-detail .release-artists-table ul{padding:0;margin:0;list-style:none}.customer-release-detail .release-artists-table li{display:flex;align-items:center;gap:6px;margin-bottom:5px}.customer-release-detail .release-track-panel{padding:0}.customer-release-detail .section-toggle-heading{margin:0;border-bottom:1px solid rgba(60,104,143,.65)}.customer-release-detail .section-toggle-btn{width:100%;padding:21px 25px;color:#f4f8ff;font-size:1.35rem}.customer-release-detail #track-info-collapse{padding:18px 24px 24px}.customer-release-detail .accordion{border:1px solid #2f5b82;border-radius:10px;overflow:hidden}.customer-release-detail .accordion-item{border:0;border-bottom:1px solid #2b547a;background:#0b2036}.customer-release-detail .accordion-button{min-height:72px;padding:14px 18px;background:#102a45;color:#edf6ff;box-shadow:none}.customer-release-detail .accordion-button:not(.collapsed){background:#173553;color:#fff}.customer-release-detail .track-number{display:grid;place-items:center;flex:none;width:30px;height:30px;margin-right:15px;border:1px solid #4a83b4;border-radius:50%;color:#9fc9ef;font-size:.76rem;font-weight:800}.customer-release-detail .track-title{font-size:.92rem;font-weight:800}.customer-release-detail .track-artist{margin-top:3px;color:#91b7da;font-size:.76rem}@media(max-width:767px){.customer-release-detail .release-overview-panel{padding:20px}.customer-release-detail .release-overview-panel:after{display:none}.customer-release-detail .release-metadata-column{padding:8px}.customer-release-detail .release-artists-table{margin-top:12px}.customer-release-detail #track-info-collapse{padding:12px}}
</style>
@endpush
@section('content')

    @if ( $release->status == 'pending' )
        <div class="alert alert-warning">
            <div class="d-flex align-items-center">
                <div class="ms-3">
                    <p>
                        This release is in verification process. You will be notified once there is an update.
                    </p>
                </div>
            </div>
        </div>
    @elseif( $release->status == 'rejected' ) 
        <div class="alert alert-danger">
            <div class="d-flex align-items-center">
                <div class="ms-3">
                    Your release is rejected by an admin. Please make necessary changes and submit revision by clicking the edit button below. If no revision is submitted in the next 7 days, this release will be deleted.
                    <div class="d-flex">
                        <a href="{{ route('releases.edit', $release->id) }}" class="btn btn-outline-primary ms-auto">Edit Release</a href="{{ route('releases.edit', $release->id) }}">
                    </div>
                </div>
            </div>
        </div>
    @endif
    @include('components.release', ['type' => 'customer'])
@endsection
