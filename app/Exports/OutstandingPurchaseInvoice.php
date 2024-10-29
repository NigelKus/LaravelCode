<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;

class OutstandingPurchaseInvoice implements FromCollection, WithHeadings, WithStyles
{
    protected $purchaseInvoice;
    protected $dates;

    public function __construct($purchaseInvoice = null, $dates = null)
    {
        $this->purchaseInvoice = $purchaseInvoice;
        $this->dates = $dates;
    }

    public function collection()
    {
        $data = collect();

        // Prepare each sales order for export
        foreach ($this->purchaseInvoice as $invoice) {
            $data->push([
                'Invoice Code' => $invoice->code,
                'Date' => $invoice->date,     
                'Customer' => $invoice->supplier->name ?? 'N/A',
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
            ['Outstanding Sales Order'],
            ['Date :', $this->dates],
            [],
            ['Invoice Code', 'Purchase Order', 'Customer', 'Description', 'Total', 'Paid', 'Remaining', 'Status'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getColumnDimension('A')->setWidth(25); // Invoice Code
        $sheet->getColumnDimension('B')->setWidth(30); // Purchase Order
        $sheet->getColumnDimension('C')->setWidth(28); // Customer
        $sheet->getColumnDimension('D')->setWidth(25); // Description
        $sheet->getColumnDimension('E')->setWidth(10); // Total
        $sheet->getColumnDimension('F')->setWidth(10); // Paid
        $sheet->getColumnDimension('G')->setWidth(10); // Remaining
        $sheet->getColumnDimension('H')->setWidth(10); // Status

        $sheet->getStyle('E:G')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // Set borders for the entire data range
        $lastRow = count($this->purchaseInvoice) + 4; // Adjust for headings and empty rows
        $sheet->getStyle("A4:H$lastRow")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Bold and center headings
        $sheet->getStyle('A4:H4')->getFont()->setBold(true);
        $sheet->getStyle('A4:H4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
}
