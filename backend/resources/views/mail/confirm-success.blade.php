<x-mail::message>
# Hello, {{$name}}

This is to confirm that you have successfully enrolled for {{$courseSession->name}}
Time is {{$courseSession->course_time}}
<br>

@if($courseSession->link)
Click on the link below to join the official Whatsapp group for the course
<x-mail::button :url="$courseSession->link" color="success">Join WhatsApp Group</x-mail::button>
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
