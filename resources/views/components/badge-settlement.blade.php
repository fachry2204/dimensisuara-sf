@props(['status'])


@if($status == 'pending')
    <span class="badge bg-warning text-dark">Pending</span>
@elseif($status == 'carried_over')
    <span class="badge bg-danger text-light">Carried Over</span>
@elseif($status == 'transferred')
    <span class="badge bg-success text-light">Transferred</span>
@else
    <span class="badge bg-secondary">{{ ucfirst($status) }}</span>
@endif