<?php

namespace App\Events;

use App\Models\Programme;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OnlineProgrammeSaved
{
    use Dispatchable, SerializesModels;

    public Programme $programme;

    /**
     * Create a new event instance.
     */
    public function __construct(Programme $programme)
    {
        $this->programme = $programme;
    }
}
