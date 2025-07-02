<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Helpers\GoogleSheets;
use Carbon\Carbon;

class UpdateAttendanceOnSheetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $attendance;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($attendance)
    {
        $this->attendance = $attendance;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = [
            "attendance" => true,
            "date" => (new Carbon($this->attendance->date))->format('d/m/Y'),
            "sheetTitle" => env('SHEET_TITLE', "Test Sheet")
        ];
        // GoogleSheets::updateGoogleSheets($this->attendance->user_id, $data);
    }
}
