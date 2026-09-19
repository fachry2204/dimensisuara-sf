@props(['status'])

@if($status == 'draft')
    <span class="badge bg-secondary">Draft</span>
@elseif($status == 'pending')
    <span class="badge bg-warning text-dark">Pending</span>
@elseif($status == 'review')
    <span class="badge bg-warning text-dark">Under Review</span>
@elseif($status == 'rejected')
    <span class="badge bg-danger text-light">Rejected</span>
@elseif($status == 'completed')
    <span class="badge bg-success text-light">Completed</span>
@elseif($status == 'expired')
    <span class="badge bg-secondary">Expired</span>
@elseif($status == 'cancelled')
    <span class="badge bg-secondary">Cancelled</span>
@else
    <span class="badge bg-secondary">{{ ucfirst($status) }}</span>
@endif
