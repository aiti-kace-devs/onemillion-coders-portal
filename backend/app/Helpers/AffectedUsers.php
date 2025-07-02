<?php
namespace App\Helpers;

use App\Models\Oex_result;
use App\Models\User;
use App\Models\user_exam;
use Carbon\Carbon;

$startTime = Carbon::yesterday()->setTime(19, 50); // Yesterday 7:50 PM
$endTime = Carbon::today()->setTime(13, 8); // Today 1:08 PM
$usersToUpdate = User::whereBetween('created_at', [$startTime, $endTime])->get();

foreach ($usersToUpdate as $user) {
    $dataToUpdate = [
        'registered' => true,
        'result' => 'N/A',
    ];
    GoogleSheets::updateGoogleSheets($user->userId, $dataToUpdate);
}

$submittedExamUsers = user_exam::whereNotNull('submitted')
    ->whereBetween('created_at', [$startTime, $endTime])
    ->get();

foreach ($submittedExamUsers as $submitted) {
    $result = Oex_result::where('user_id', $submitted->user_id)
        ->where('exam_id', $submitted->exam_id)
        ->first();
    $user = User::find($submitted->user_id);
    GoogleSheets::updateGoogleSheets($user->userId, [
        'result' => $result->yes_ans,
    ]);
}
