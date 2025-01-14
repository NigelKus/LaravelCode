<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BalanceSheetExport implements FromCollection, WithHeadings, WithStyles
{
    protected $dateStringDisplay;
    protected $totalasset;
    protected $totalUtang;
    protected $totalLaba;
    protected $totalModal;
    protected $codeModal;
    protected $codeLaba;
    protected $totalActiva;
    protected $totalPasiva;
    protected $createddate;

    protected $codeLabaBertahan; 
    protected $totalLabaBerjalan;

    public function __construct($dateStringDisplay, $totalasset, $totalUtang, $totalLaba, $totalModal, $codeModal, $codeLaba, $totalActiva, $totalPasiva, $createddate, $codeLabaBertahan, $totalLabaBerjalan)
    {
        $this->dateStringDisplay = $dateStringDisplay;
        $this->totalasset = $totalasset;
        $this->totalUtang = $totalUtang;
        $this->totalLaba = $totalLaba;
        $this->totalModal = $totalModal;
        $this->codeModal = $codeModal;
        $this->codeLaba = $codeLaba;
        $this->totalActiva = $totalActiva;
        $this->totalPasiva = $totalPasiva;
        $this->createddate = $createddate;
        $this->$codeLabaBertahan = $codeLabaBertahan;
        $this->$totalLabaBerjalan = $totalLabaBerjalan;
    }
    public function collection()
    {
        $data = collect();

        $data->push(
            [''],
            ['Asset'],
            ['Nama', 'Jumlah']
        );

        foreach ($this->totalasset as $a) {
            $jumlah = number_format(abs($a['total']), 2);
            $data->push([
                "{$a['coa']->name} ({$a['coa']->code})", 
                $jumlah, 
                '' 
            ]);
        }

        $data->push([
            'Total Asset',
            number_format($this->totalActiva, 2)
        ]);

        $data->push(
            [''],
            ['Liabilities & Equity'],
            ['Nama', 'Jumlah'],
            ['Liability']
        );

        $totalLiability = 0; 
        foreach ($this->totalUtang as $a) {
            $jumlah = number_format(abs($a['total']), 2);
            $totalLiability += abs($a['total']); 
            $data->push([
                "{$a['coa']->name} ({$a['coa']->code})", 
                $jumlah, 
                '' 
            ]);
        }

        $data->push(
            ['Equity']
        );

        if($this->totalModal) {
            $jumlah = number_format(abs($this->totalModal), 2);
            $data->push([
                $this->codeModal->name . ' (' . $this->codeModal->code . ')', 
                $jumlah, 
                '' 
            ]);
        }
        

        if($this->totalLaba)
        {
            $jumlah = number_format(abs($this->totalLaba), 2);
            $data->push([
                'Laba Berjalan'. ' ('. $this->codeLaba->code.')', 
                $jumlah, 
                '' 
            ]);
        }

        if($this->totalLabaBerjalan)
        {
            $jumlah = number_format(abs($this->totalLaba), 2);
            $data->push([
                'Laba Bertahan'. ' ('. $this->codeLabaBertahan->code.')', 
                $jumlah, 
                '' 
            ]);
        }

        $data->push([
            'Total Liability & Equity',
            number_format($this->totalPasiva, 2)
        ]);

        return $data;
    }

    public function headings(): array
    {
        return [
            ['Laporan Neraca Saldo'],
            ['Tanggal :', $this->dateStringDisplay],
            ['Dibuat pada : ', $this->createddate],
            [],
            [],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getColumnDimension('A')->setWidth(25); // Nama
        $sheet->getColumnDimension('B')->setWidth(30); // Jumlah

        $sheet->getStyle('E:G')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        
    }
}