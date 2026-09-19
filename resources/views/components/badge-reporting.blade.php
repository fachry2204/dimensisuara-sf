@props(['status'])

@if($status == 'pending_import')
    <span class="badge bg-warning text-dark">Pending Import</span>
@elseif($status == 'pending_review')
    <span class="badge bg-warning text-dark">Pending Review</span>
@elseif($status == 'pending_rate')
    <span class="badge bg-warning text-dark">Pending Rate</span>
@elseif($status == 'pending_calculation')
    <span class="badge bg-warning text-dark">Pending Calculation</span>
@elseif($status == 'failed')
    <span class="badge bg-danger text-light">Failed</span>
@elseif($status == 'completed')
    <span class="badge bg-success text-light">Completed</span>
@else
    <span class="badge bg-secondary">{{ ucfirst($status) }}</span>
@endif