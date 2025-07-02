<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Helpers\GoogleSheets;

class UpdateSheetWithGhanaCardDetails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $student;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($student)
    {
        $this->student = $student;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = [
            "verification" => true,
            "card_number" => $this->student->ghcard,
            "name" => strtoupper($this->student->name),
            "sheetTitle" => env('SHEET_TITLE', "Test Sheet"),
        ];
        // GoogleSheets::updateGoogleSheets($this->student->userId, $data);
    }
}
