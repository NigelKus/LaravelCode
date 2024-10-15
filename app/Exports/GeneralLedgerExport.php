<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GeneralLedgerExport implements FromCollection, WithHeadings, WithStyles
{
    protected $results;
    protected $fromdate;
    protected $todate;
    protected $date;
    protected $postings;
    protected $balance;
    protected $coa;
    protected $all;

    public function __construct(array $results = null, $fromdate, $todate, $date, $postings = null, $balance = null, $coa = null, $all = null)
    {
        $this->results = $results;
        $this->fromdate = $fromdate;
        $this->todate = $todate;
        $this->date = $date;
        $this->postings = $postings;
        $this->balance = $balance;
        $this->coa = $coa;
        $this->all = $all;
    }

    public function collection()
    {
        $data = collect();

        $data->push(['']);

        if($this->all == true){
        foreach ($this->results as $result) {
            $totalKredit = 0;
            $totalDebit = 0;
            $totalBalance = 0;

            $startingBalance = !empty($result['starting_balance']) ? $result['starting_balance'] : 0;

            $data->push([
                'Chart of Account' => $result['coa'].'('.$result['coa_code'].')',
            ]);
            $data->push([
                'Kode',
                'Tanggal',
                'Journal Name',
                'Kode Transaksi',
                'Debit',
                'Kredit',
                'Balance',
            ]);

            $data->push([
                'Saldo Awal',
                $this->date,
                'Saldo Awal',
                '',
                '',
                '',
                number_format($startingBalance, 0, '.', ','), 
            ]);
            $totalBalance += $startingBalance;

            foreach ($result['postings'] as $posting) {
                $dataArray = [
                    'Kode' => $posting->journal->code,
                    'Tanggal' => $posting->date,
                    'Journal Name' => $posting->journal->name,
                    'Kode Transaksi' => $posting->journal->description,
                    'Debit' => '',
                    'Kredit' => '',
                    'Balance' => '', 
                ];

                if ($posting->amount >= 0) {
                    $dataArray['Debit'] = number_format($posting->amount, 0, '.', ',');
                    $totalDebit += $posting->amount;
                    $totalBalance += $posting->amount; 
                } else {
                    $dataArray['Kredit'] = number_format(abs($posting->amount), 0, '.', ','); 
                    $totalKredit += abs($posting->amount);
                    $totalBalance -= abs($posting->amount); 
                }

                $dataArray['Balance'] = number_format($totalBalance, 0, '.', ','); 
                $data->push($dataArray);
            }

            // Totals row formatted
            $data->push([
                '',
                '',
                '',
                'Total',
                number_format($totalDebit, 0, '.', ','), 
                number_format($totalKredit, 0, '.', ','), 
                number_format($totalBalance, 0, '.', ','), 
            ]);

            $data->push(['']);
        }
            return $data;
        }else{
            $totalKredit = 0;
            $totalDebit = 0;
            $totalBalance = 0;

            $startingBalance = !empty($this->balance) ? $this->balance : 0;

            $data->push([
                'Chart of Account' => $this->coa->name.'('.$this->coa->code.')',
            ]);
            $data->push([
                'Kode',
                'Tanggal',
                'Journal Name',
                'Kode Transaksi',
                'Debit',
                'Kredit',
                'Balance',
            ]);

            $data->push([
                'Saldo Awal',
                $this->date,
                'Saldo Awal',
                '',
                '',
                '',
                number_format($startingBalance, 0, '.', ','), 
            ]);
            $totalBalance += $startingBalance;

            foreach ($this->postings as $posting) {
                $dataArray = [
                    'Kode' => $posting->journal->code,
                    'Tanggal' => $posting->date,
                    'Journal Name' => $posting->journal->name,
                    'Kode Transaksi' => $posting->journal->description,
                    'Debit' => '',
                    'Kredit' => '',
                    'Balance' => '', 
                ];

                if ($posting->amount >= 0) {
                    $dataArray['Debit'] = number_format($posting->amount, 0, '.', ',');
                    $totalDebit += $posting->amount;
                    $totalBalance += $posting->amount; 
                } else {
                    $dataArray['Kredit'] = number_format(abs($posting->amount), 0, '.', ','); 
                    $totalKredit += abs($posting->amount);
                    $totalBalance -= abs($posting->amount); 
                }

                $dataArray['Balance'] = number_format($totalBalance, 0, '.', ','); 
                $data->push($dataArray);
            }

            $data->push([
                '',
                '',
                '',
                'Total',
                number_format($totalDebit, 0, '.', ','), 
                number_format($totalKredit, 0, '.', ','), 
                number_format($totalBalance, 0, '.', ','), 
            ]);

            $data->push(['']);
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            ['General Ledger Report'],
            ['Date Range:', $this->fromdate . ' s/d ' . $this->todate],
            ['Created Date:', $this->date],
            [],
            [],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getColumnDimension('A')->setWidth(25); // Kode
        $sheet->getColumnDimension('B')->setWidth(30); // Tanggal
        $sheet->getColumnDimension('C')->setWidth(28); // Journal Name
        $sheet->getColumnDimension('D')->setWidth(25); // Kode Transaksi
        $sheet->getColumnDimension('E')->setWidth(15); // Debit
        $sheet->getColumnDimension('F')->setWidth(15); // Kredit
        $sheet->getColumnDimension('G')->setWidth(15); // Balance

        $sheet->getStyle('E:G')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    }

}
