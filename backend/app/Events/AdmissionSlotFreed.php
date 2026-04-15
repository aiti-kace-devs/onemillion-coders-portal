<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\ProgrammeBatch;
use App\Models\UserAdmission;

class AdmissionSlotFreed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ProgrammeBatch $programmeBatch,
        public ?UserAdmission $admission = null
    ) {}
}
