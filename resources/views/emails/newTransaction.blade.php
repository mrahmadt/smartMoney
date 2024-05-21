<div>{{$body}}</div>


@if($error)
<br><hr><br>
<div>
<b>Errors:</b><br>
<pre>
@php
    print_r($error, true)
@endphp
</pre>
</div>
@endif