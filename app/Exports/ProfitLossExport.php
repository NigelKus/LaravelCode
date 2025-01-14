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
    protected $HPP;
    protected $date;
    protected $totalHPP;
    protected $saldoAwal;


    public function __construct($fromdate, $todate, $pendapatan, $beban, $HPP, $date, $totalHPP, $saldoAwal)
    {
        $this->fromdate = $fromdate;
        $this->todate = $todate;
        $this->pendapatan = $pendapatan;
        $this->beban = $beban;
        $this->HPP = $HPP;
        $this->date = $date;
        $this->totalHPP = $totalHPP;
        $this->saldoAwal = $saldoAwal;
    }
    public function collection()
    {
        $data = collect();
    
        $data->push(['']); 
        $data->push(['Keterangan', 'Jumlah', 'Total']);
    
        $totalPendapatan = 0;

        $saldoAwal = $this->saldoAwal;
        
        $data->push(['Saldo Awal', $this->saldoAwal]);

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
            'Harga Pokok Penjualan (4200)',
            '',
            number_format($this->totalHPP, 2), 
        ]);

        $labaKotor = $totalPendapatan - $this->totalHPP;

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
    
        $labaBersihSebelumPajak = $labaKotor - $totalBeban + $saldoAwal; 
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
            ['Laporan Laba Rugi'],
            ['Tanggal :', $this->fromdate . ' s/d ' . $this->todate],
            ['Dibuat pada :', $this->date],
            [],
            [],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getColumnDimension('A')->setWidth(27); // Keterangan
        $sheet->getColumnDimension('B')->setWidth(38); // Jumlah
        $sheet->getColumnDimension('C')->setWidth(28); // Total

        $sheet->getStyle('B:C')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getStyle('A1:A5')->getFont()->setBold(true);

        $sheet->getStyle('A5:C5')->getFont()->setBold(true);

        
    }

}
