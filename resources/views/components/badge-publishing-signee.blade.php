@props(['status'])

@if($status == 'pending')
    <span class="badge bg-warning text-dark">Sign Pending</span>
@elseif($status == 'rejected')
    <span class="badge bg-danger text-light">Sign Rejected</span>
@elseif($status == 'accepted')
    <span class="badge bg-success text-light">Sign Approved</span>
@elseif($status == 'expired')
    <span class="badge bg-secondary">Expired</span>
@elseif($status == 'cancelled')
    <span class="badge bg-secondary">Cancelled</span>
@else
    <span class="badge bg-secondary">{{ ucfirst($status) }}</span>
@endif
