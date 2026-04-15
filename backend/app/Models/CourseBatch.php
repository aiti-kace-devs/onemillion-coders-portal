<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @deprecated Use ProgrammeBatch instead. This class is a thin alias for legacy compatibility.
 */
class CourseBatch extends ProgrammeBatch
{
    use CrudTrait;
    use HasFactory;

    protected $table = 'programme_batches';
}
