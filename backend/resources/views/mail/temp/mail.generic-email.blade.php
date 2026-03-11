<x-mail::message><h1>Hello, AGYEI EUNICE</h1>
<p>This is to confirm that you have successfully enrolled for <strong>Certified Network Support Technician (CNST) - (Bono Region) Afternoon Session</strong>   <br>
Time is <strong>2pm - 7pm</strong></p>
<p>@if($data['link'])
Click on the link below to join the official Whatsapp group for the course
<x-mail::button url="https://chat.whatsapp.com/BekTu3PWEqc8UtydifN8Mt" color="success">Join WhatsApp Group</x-mail::button>
@endif</p>
   <br>   Thanks,   {{ config('app.name') }}</x-mail::message>