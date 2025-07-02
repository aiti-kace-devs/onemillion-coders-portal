@props(['date', 'registered'])

@php
    $registered = isset($registered) ? $registered : auth()->user()->created_at;
    $now = Carbon\Carbon::now();
    $leftToDeadline = $now->diffInHours(new Carbon\Carbon($date));

    $deadline = $date;
    $hoursLeft = $leftToDeadline;

    $studentDeadline = (new Carbon\Carbon($registered))->addDays(config(EXAM_DEADLINE_AFTER_REGISTRATION, 2));
    $studentHoursLeft = $now->diffInHours($studentDeadline);

    if ($studentHoursLeft < $leftToDeadline) {
        $deadline = $studentDeadline->toDateString();
        $hoursLeft = $studentHoursLeft;
    }
@endphp

@if ($now->isAfter($deadline))
@else
    {{ $slot }}
@endif
