<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Form;

class FormPreviewController extends Controller
{
    public function preview(Form $form)
    {
        $schema = $form->schema;
        return view('form.preview', compact('form', 'schema'));
    }

}
