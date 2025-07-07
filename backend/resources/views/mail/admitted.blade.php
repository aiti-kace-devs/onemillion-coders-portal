<x-mail::message>
# Dear {{$name}},

Congratulations on your selection as one of the shortlisted participants for <b>{{$course->course_name}}</b>.
This is a final confirmation phase to ensure your availability for the training. Kindly take note of the following details:

Start Date: <b>{{(new Carbon\Carbon($course->start_date ?? $programme->start_date))->format('l jS F, Y')}}.</b><br>
Training Duration: <b>{{$course->duration ?? $programme->duration}}.</b><br>
Venue: <b>{{$centre->title}}.</b><br>
Kindly select a session that fits your schedule by clicking on the "Select Session" button below.
<x-mail::button :url="$url">Select Session</x-mail::button>

<x-mail::panel>
Note: Only applicants who have selected their sessions will move to the next stage.
</x-mail::panel>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
