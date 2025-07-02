@component('mail::message')
# Welcome, {{ $name }} !

We are excited to have you. Here are your participant login details:
@component('mail::panel')
- **Email:** {{ $email }}
- **Password:** {{ $password }}
@endcomponent

You can log in and start your assessment test by clicking the button below:

@component('mail::button', ['url' => $examUrl, 'color' => 'error'])
Start Your Assessment Test
@endcomponent

@component('mail::panel')

Your deadline for submission is {{$deadline}}
If you are having trouble with the button copy and paste this URL in a browser: <a href="{{$examUrl}}">{{$examUrl}}</a>
@endcomponent

Thanks,
{{ config('app.name') }}
@endcomponent