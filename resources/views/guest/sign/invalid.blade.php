@extends(auth()->check() ? 'layouts.app' : 'layouts.guest')

@section('title')
Request No Longer available
@endsection

@section('content')
<div class="pt-5">
    <div class="container pt-5">
        <div class="row pt-5">
            <div class="col-lg-12">
                <div class="error-page">
                    <h2 class="headline text-warning"> 410</h2>
                
                    <div class="error-content">
                        <h3><i class="fas fa-exclamation-triangle text-warning"></i> Oops! Request No longer available.</h3>
                
                        <p>
                            The signing request is no longer available.
                            Meanwhile, you may return to <a href="{{ route('login') }}">main page</a>.
                        </p>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
