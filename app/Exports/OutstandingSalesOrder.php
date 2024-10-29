<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;

class OutstandingSalesOrder implements FromCollection, WithHeadings, WithStyles
{
    protected $salesOrder;
    protected $dates;

    public function __construct($salesOrder = null, $dates = null)
    {
        $this->salesOrder = $salesOrder;
        $this->dates = $dates;
    }

    public function collection()
    {
        $data = collect();

        // Prepare each sales order for export
        foreach ($this->salesOrder as $order) {
            $data->push([
                'Order Code' => $order->code,
                'Date' => $order->date,     
                'Customer' => $order->customer->name ?? 'N/A',
                'Description' => $order->description,
                'Quantity' => $order->total_quantity,
                'Sent' => $order->total_quantity_sent,
                'Remaining' => $order->quantity_difference,
                'Status' => $order->status,
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
            ['Order Code', 'Date', 'Customer', 'Description', 'Quantity', 'Sent', 'Remaining', 'Status'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getColumnDimension('A')->setWidth(25); // Order Code
        $sheet->getColumnDimension('B')->setWidth(30); // Date
        $sheet->getColumnDimension('C')->setWidth(28); // Customer
        $sheet->getColumnDimension('D')->setWidth(25); // Description
        $sheet->getColumnDimension('E')->setWidth(10); // Quantity
        $sheet->getColumnDimension('F')->setWidth(10); // Quantity Sent
        $sheet->getColumnDimension('G')->setWidth(10); // Quantity Remaining

        $sheet->getStyle('E:G')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // Set borders for the entire data range
        $lastRow = count($this->salesOrder) + 4; // Adjust for headings and empty rows
        $sheet->getStyle("A4:H$lastRow")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Bold and center headings
        $sheet->getStyle('A4:H4')->getFont()->setBold(true);
        $sheet->getStyle('A4:H4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
}
