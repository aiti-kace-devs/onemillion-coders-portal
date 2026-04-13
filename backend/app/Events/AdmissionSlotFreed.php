<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdmissionSlotFreed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ?int $courseId,
        public readonly ?int $programmeBatchId = null
    ) {}
}
