@extends(auth()->check() && auth()->user()->hasVerifiedEmail() ? 'layouts.app' : 'layouts.guest')

@section('title')
419 Page Expired
@endsection

@section('content')
<div class="pt-5">
    <div class="container pt-5">
        <div class="row pt-5">
            <div class="col-lg-12">
                <div class="error-page">
                    <h2 class="headline text-warning"> 419</h2>
                
                    <div class="error-content">
                        <h3><i class="fas fa-exclamation-triangle text-warning"></i> Oops! Page Expired.</h3>
                
                        <p>
                            The page you requested was expired.
                            Meanwhile, you may return to <a href="{{ route('login') }}">main page</a>.
                        </p>

                        @can('view-logging')
                            @if($exception->getMessage())
                                <p>
                                    <strong>Details:</strong><br>
                                    <code>{{ $exception->getMessage() }}</code>
                                </p>
                            @endif
                        @endcan
                        
                    </div>
                
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
