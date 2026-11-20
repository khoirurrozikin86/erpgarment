<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DefaultValueBinder, DataType};

class OutgoingGoodsTraceExport extends DefaultValueBinder implements FromView, WithCustomValueBinder
{
    public function __construct(private array $data) {}
    public function view(): View { return view('super.reports.outgoing.sections', [...$this->data, 'spreadsheet' => true]); }
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) { $cell->setValueExplicit($value, DataType::TYPE_STRING); return true; }
        return parent::bindValue($cell, $value);
    }
}
