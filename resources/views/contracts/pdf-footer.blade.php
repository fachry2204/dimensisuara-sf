<div class="contract-pdf-footer">@foreach($rows as $row)<div class="contract-footer-row">@if($row['bold'])<strong>{{ $row['text'] }}</strong>@else{{ $row['text'] }}@endif</div>@endforeach</div>
