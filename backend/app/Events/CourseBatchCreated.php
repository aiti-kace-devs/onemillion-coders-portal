<?php

namespace App\Events;

use App\Models\CourseBatch;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourseBatchCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly CourseBatch $courseBatch) {}
}
