<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;

class OutstandingSalesInvoice implements FromCollection, WithHeadings, WithStyles
{
    protected $salesInvoice;
    protected $dates;
    protected $displaydate;
    protected $createddate;


    public function __construct($salesInvoice = null, $dates = null, $displaydate, $createddate)
    {
        $this->salesInvoice = $salesInvoice;
        $this->dates = $dates;
        $this->displaydate = $displaydate;
        $this->createddate = $createddate;
    }

    public function collection()
    {
        $data = collect();

        // Prepare each sales order for export
        foreach ($this->salesInvoice as $invoice) {
            $data->push([
                'Invoice Code' => $invoice->code,
                'Date' => $invoice->date,     
                'Customer' => $invoice->customer->name ?? 'N/A',
                'Description' => $invoice->description,
                'Total' => $invoice->total_price,
                'Paid' => $invoice->paid,
                'Remaining' => $invoice->remaining_price,
                'Status' => $invoice->status,
            ]);
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            ['Outstanding Sales Order List'],
            ['Date :', $this->displaydate],
            ['Created at :', $this->createddate],
            ['Invoice Code', 'Date', 'Customer', 'Description', 'Total', 'Paid', 'Remaining', 'Status'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getColumnDimension('A')->setWidth(25); // Invoice Code
        $sheet->getColumnDimension('B')->setWidth(30); // Date
        $sheet->getColumnDimension('C')->setWidth(28); // Customer
        $sheet->getColumnDimension('D')->setWidth(25); // Description
        $sheet->getColumnDimension('E')->setWidth(10); // Total
        $sheet->getColumnDimension('F')->setWidth(10); // Paid
        $sheet->getColumnDimension('G')->setWidth(10); // Remaining
        $sheet->getColumnDimension('H')->setWidth(10); // Status

        $sheet->getStyle('E:G')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // Set borders for the entire data range
        $lastRow = count($this->salesInvoice) + 4; // Adjust for headings and empty rows
        $sheet->getStyle("A4:H$lastRow")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Bold and center headings
        $sheet->getStyle('A4:H4')->getFont()->setBold(true);
        $sheet->getStyle('A4:H4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
}
