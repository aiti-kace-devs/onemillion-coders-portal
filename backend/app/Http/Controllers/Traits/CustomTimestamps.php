<?php

namespace App\Http\Controllers\Traits;

trait CustomTimestamps
{

    /**
     * Get the name of the "created at" column.
     *
     * @return string
     */
    public function getCreatedAtColumn()
    {
        return 'created_on';
    }

    /**
     * Get the name of the "updated at" column.
     *
     * @return string
     */
    public function getUpdatedAtColumn()
    {
        return 'updated_on';
    }
}
