<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FormResponseExport implements FromCollection, WithHeadings
{
    protected $headers;
    protected $data;

    /**
    * @return \Illuminate\Support\Collection
    */

    public function __construct(array $headers, $data)
    {
        $this->headers = $headers;
        $this->data = $data;
    }
    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return $this->headers;
    }
}
