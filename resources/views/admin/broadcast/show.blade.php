@extends('layouts.app')
@section('title')
Broadcast Details
@endsection
@section('header')
    <div class="d-flex flex-row align-items-center justify-content-between">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.broadcast.index') }}">Broadcast Messages</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Details</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="d-flex flex-row align-items-center justify-content-between mb-5">
        <h4 class="mb-0 fs-2">Broadcast Details</h4>
        <div class="d-flex gap-2">
            @can('broadcast')
                @if($broadcast->status === 'draft')
                    <a href="{{ route('admin.broadcast.edit', $broadcast->id) }}" class="btn btn-outline-primary d-flex align-items-center">
                        <span class="material-icons-outlined me-1">edit</span>
                        Edit
                    </a>
                    <form action="{{ route('admin.broadcast.destroy', $broadcast->id) }}" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger d-flex align-items-center"
                                onclick="return confirm('Are you sure you want to delete this broadcast?')">
                            <span class="material-icons-outlined me-1">delete</span>
                            Delete
                        </button>
                    </form>
                @endif
            @endcan
        </div>
    </div>
@endsection
@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Message Content</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <div>
                            @if($broadcast->status === 'draft')
                                <span class="badge bg-secondary">Draft</span>
                            @elseif($broadcast->status === 'pending')
                                <span class="badge bg-warning">Sending...</span>
                                <small class="text-muted ms-2">
                                    Broadcast is being sent in the background
                                    @if($broadcast->total_chunks > 0)
                                        — {{ $broadcast->completed_chunks }} of {{ $broadcast->total_chunks }} chunks completed
                                    @endif
                                </small>
                            @elseif($broadcast->status === 'failed')
                                <span class="badge bg-danger">Failed</span>
                                @if($broadcast->total_chunks > 0)
                                    <small class="text-muted ms-2">
                                        {{ $broadcast->completed_chunks }} of {{ $broadcast->total_chunks }} chunks completed.
                                        Please check the logs for details.
                                    </small>
                                @endif
                            @else
                                <span class="badge bg-success">Sent</span>
                                @if($broadcast->sent_at)
                                    <small class="text-muted ms-2">Sent on {{ $broadcast->sent_at->format('F d, Y H:i') }}</small>
                                @endif
                            @endif
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Subject</label>
                        <p>{{ $broadcast->subject }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Message</label>
                        <div class="p-3 bg-body-secondary rounded">
                            {!! nl2br(e($broadcast->message)) !!}
                        </div>
                    </div>

                    @if($broadcast->status === 'draft')
                        <div class="mt-4">
                            <form action="{{ route('admin.broadcast.send', $broadcast->id) }}" method="POST"
                                  onsubmit="return confirm('Are you sure you want to send this broadcast to {{ $broadcast->recipient_count }} recipients? This action cannot be undone.');">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center">
                                    <span class="material-icons-outlined me-1">send</span>
                                    Send Broadcast to {{ $broadcast->recipient_count }} Recipients
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Broadcast Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Created By</label>
                        <p>{{ $broadcast->creator->name }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Created Date</label>
                        <p>{{ $broadcast->created_at->format('F d, Y H:i') }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Last Updated</label>
                        <p>{{ $broadcast->updated_at->format('F d, Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">Recipients</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">Selection Mode:</span>
                        <strong>
                            @if($broadcast->selection_mode === 'include')
                                <span class="badge bg-success">Include Mode</span>
                            @else
                                <span class="badge bg-info">Exclude Mode</span>
                            @endif
                        </strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">Total Users:</span>
                        <strong>{{ $totalUsers }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">
                            @if($broadcast->selection_mode === 'include')
                                Selected:
                            @else
                                Excluded:
                            @endif
                        </span>
                        <strong>{{ $excludedCount }}</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold">Recipients:</span>
                        <strong class="text-primary fs-5">{{ $broadcast->recipient_count }}</strong>
                    </div>

                    @if($excludedCount > 0)
                        <div class="mt-3">
                            <details>
                                <summary class="cursor-pointer text-muted">
                                    <small>
                                        @if($broadcast->selection_mode === 'include')
                                            Show selected users ({{ $excludedCount }})
                                        @else
                                            Show excluded users ({{ $excludedCount }})
                                        @endif
                                    </small>
                                </summary>
                                <div class="mt-2">
                                    @if(count($selectedUsers) > 0)
                                        <ul class="list-unstyled mb-0">
                                            @foreach($selectedUsers as $user)
                                                <li class="py-1">
                                                    <small>
                                                        <strong>{{ $user['full_name'] }}</strong>
                                                        <span class="text-muted">({{ $user['email'] }})</span>
                                                    </small>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <small class="text-muted">No users selected</small>
                                    @endif
                                </div>
                            </details>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
