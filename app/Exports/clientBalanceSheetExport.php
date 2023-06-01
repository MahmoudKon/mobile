<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;


class clientBalanceSheetExport implements FromView
{
    private $transactions, $escaped_details, $pre_balance, $file_name;

    public function __construct($transactions, $escaped_details, $pre_balance)
    {
        $this->transactions = $transactions;
        $this->escaped_details = $escaped_details;
        $this->pre_balance = $pre_balance;
    }

    // /**
    // * @return \Illuminate\Support\Collection
    // */
    // public function collection()
    // {
    //     return $this->transactions;
    // }

    public function view(): view
    {
        return view('api.balance_sheet_table', [
            'collection' => $this->transactions,
            'deleted_bills' => $this->escaped_details,
            'pre_balance' => $this->pre_balance
        ]);
    }
}
