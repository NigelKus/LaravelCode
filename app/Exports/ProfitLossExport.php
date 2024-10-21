<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProfitLossExport implements FromCollection, WithHeadings, WithStyles
{
    protected $fromdate;
    protected $todate;
    protected $pendapatan;
    protected $beban;
    protected $stock;
    protected $date;

    public function __construct($fromdate, $todate, $pendapatan, $beban, $stock, $date)
    {
        $this->fromdate = $fromdate;
        $this->todate = $todate;
        $this->pendapatan = $pendapatan;
        $this->beban = $beban;
        $this->stock = $stock;
        $this->date = $date;
    }
    public function collection()
    {
        $data = collect();
    
        $data->push(['']); 
        $data->push(['Keterangan', 'Jumlah', 'Total']);
    
        $totalPendapatan = 0; 
        foreach ($this->pendapatan as $a) {
            $jumlah = number_format(abs($a['total']), 2);
            $totalPendapatan += abs($a['total']);
            $data->push([
                "{$a['coa']->name} ({$a['coa']->code})", 
                $jumlah, 
                '' 
            ]);
        }
        
        $data->push([
            'Total Pendapatan',
            '',
            number_format($totalPendapatan, 2) 
        ]);
    
        $data->push([
            'HPP',
            '',
            number_format($this->stock, 2), 
        ]);

        $labaKotor = $totalPendapatan - $this->stock;

        $data->push([
            'Laba Kotor',
            '',
            number_format($labaKotor, 2), 
        ]);
    
        $totalBeban = 0; 
        foreach ($this->beban as $a) {
            $jumlah = number_format(abs($a['total']), 2);
            $totalBeban += abs($a['total']); 
            $data->push([
                "{$a['coa']->name} ({$a['coa']->code})", 
                $jumlah, 
                '' 
            ]);
        }
    
        $data->push([
            'Total Beban',
            '',
            number_format($totalBeban, 2)
        ]);
    
        $labaBersihSebelumPajak = $labaKotor - $totalBeban ; 
        $data->push([
            'Laba Bersih Sebelum Pajak',
            '',
            number_format($labaBersihSebelumPajak, 2) 
        ]);
    
        return $data;
    }
    

    public function headings(): array
    {
        return [
            ['Profit Loss Report'],
            ['Date Range:', $this->fromdate . ' s/d ' . $this->todate],
            ['Created Date:', $this->date],
            [],
            [],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getColumnDimension('A')->setWidth(27); // Keterangan
        $sheet->getColumnDimension('B')->setWidth(32); // Jumlah
        $sheet->getColumnDimension('C')->setWidth(28); // Total

        $sheet->getStyle('B:C')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle('A1:A5')->getFont()->setBold(true);

        $sheet->getStyle('A5:C5')->getFont()->setBold(true);

        
    }

}
