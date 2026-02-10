<?php

namespace App\Events;

use App\Models\UserAdmission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdmissionRejected
{
    use Dispatchable, SerializesModels;

    public UserAdmission $admission;
    public bool $shouldReplace;

    /**
     * Create a new event instance.
     *
     * @param UserAdmission $admission The admission being rejected
     * @param bool $shouldReplace Whether to automatically find and admit a replacement
     */
    public function __construct(UserAdmission $admission, bool $shouldReplace = true)
    {
        $this->admission = $admission;
        $this->shouldReplace = $shouldReplace;
    }
}
