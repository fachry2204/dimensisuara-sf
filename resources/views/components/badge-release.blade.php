@props(['status'])

@if($status == 'draft')
    <span class="badge bg-secondary">Draft</span>
@elseif($status == 'pending')
    <span class="badge bg-warning text-dark">Pending</span>
@elseif($status == 'review')
    <span class="badge release-status-review">Under Review</span>
@elseif($status == 'revision')
    <span class="badge release-status-revision">Revision</span>
@elseif($status == 'rejected')
    <span class="badge bg-danger text-light">Rejected</span>
@elseif($status == 'completed')
    <span class="badge bg-success text-light">Completed</span>
@elseif($status == 'released')
    <span class="badge bg-success text-light">Published</span>
@elseif($status == 'uploading')
    <span class="badge bg-info text-dark">Upload DSP</span>
@else
    <span class="badge bg-secondary">{{ ucfirst($status) }}</span>
@endif
