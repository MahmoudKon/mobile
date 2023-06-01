<?php

namespace App\Services;

use App\BackDetails;
use App\BackProcess;
use App\Badrshop;
use App\Client;
use Illuminate\Http\Request;
use App\ClientTransaction;
use App\IncomeBill;
use App\IncomeBillReturn;
use App\SaleDetails;
use App\SalePoint;
use App\SaleProcess;
use App\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BalanceSheetCalc
{

    private $transactionFactory;
    private $balanceSheetRepo;

    public function __construct(TransactionFactory $transactionFactory, BalanceSheetRepository $balanceSheetRepository)
    {
        $this->transactionFactory = $transactionFactory;
        $this->balanceSheetRepo = $balanceSheetRepository;
    }

    public function showNew($id, Request $request)
    {
        ini_set('max_execution_time', 0);
        ini_set('max_input_time', 0);
        ini_set('memory_limit', '256M');

        $this->updateUnknownClientTransactions($id);
        $pay_day = ClientTransaction::where('shop_id', auth()->guard('rep')->user()->shop_id)->orderBy('pay_day', 'ASC')->first();
        if (is_null($pay_day) || ($pay_day && $pay_day->pay_day == '0000-00-00')) {
            $activ = Badrshop::where('serial_id', auth()->guard('rep')->user()->shop_id)->first()->active_date;
        } else {
            $activ = $pay_day->pay_day;
        }
        $from = Carbon::parse($activ)->format('Y-m-d');

        $to = '';

        #### get content sheet transactions and deleted bills #### 
        $status = 'content';
        $collectionData = $this->getClientSheet($id, $from, $to, $status);
        $collection = $collectionData['data'];
        $deleted_bills = $collectionData['skip'];

        #### get previous sheet balance ####
        $status = 'prev';
        $pre_sheetData = $this->getClientSheet($id, $from, $to, $status);
        $pre_sheet = $pre_sheetData['data'];

        $pre_balance = 0;
        $balance = 0;
        if (count($pre_sheet) > 0) {
            foreach ($pre_sheet as $sheet) {
                if (in_array($sheet->bill_id, $deleted_bills) || in_array($sheet->type, [34, 30, 31])) {
                } else {
                    if (!in_array($sheet->type, [34, 30, 31])) {
                        $balance = $sheet->balance;
                        if (!in_array($sheet->type, [4, 8])) {
                            $balance = balance($sheet, $pre_balance);
                        }
                        if (in_array($sheet->type, [0, 1, 34, 30, 31]) && $sheet->has_bill == false) {
                            $balance = $pre_balance;
                        }
                    }
                }
                $pre_balance = $balance;
            }
        }

        if ($request->has('pre_balance')) {
            $pre_balance = $request->pre_balance;
        }

        $shop = DB::table('badr_shop')->where('serial_id', auth()->guard('rep')->user()->shop_id)->first();

        $client = Client::where('shop_id', auth()->guard('rep')->user()->shop_id)->where('id', $id)->first();
        $group = \DB::table('clients_groups')->find($client->group_id)->name ?? '';

        $client->group_name = $group;
        $newEntry = ClientTransaction::where('client_transaction.shop_id', auth()->guard('rep')->user()->shop_id)
            ->where('client_transaction.client_id', $id)->where('type', 4)->first();
        // dd($collection);
        if ($request->updateBalance == '1') {
            //            return $collection;
            $data = [
                'collection' => $collection,
                'deleted_bills' => $deleted_bills,
                'pre_balance' => $pre_balance,
                'newEntry' => $newEntry
            ];
            return $data;
        }

        return [$collection, $deleted_bills, $pre_balance];
    }

    public function getClientSheet($id, $from, $to, $status)
    {
        $clientTransactions = $this->balanceSheetRepo->balanceSheet($id, $from, $to, $status);

        $deleted_bills = [];
        $collection = collect();
        $clientTransactions->chunk(20)->each(function ($chucked_transactions) use (&$collection, &$deleted_bills) {
            $chucked_transactions->each(function ($ct) use (&$collection, &$deleted_bills) {

                $billCheck = null;
                $point = null;
                if ($ct->type == 1) {
                    $billCheck = SaleProcess::find($ct->bill_id);
                    $saleDetails = $billCheck ? SaleDetails::where('sale_id', $ct->bill_id)->get() : null;
                    $point = SalePoint::find($billCheck ? $billCheck->sale_point : 0);
                } else if ($ct->type == 0) {
                    $billCheck = BackProcess::find($ct->sale_back_id);
                    $backDetails = $billCheck ? BackDetails::where('back_id', $ct->sale_back_id)->get() : null;
                    $point = SalePoint::find($backDetails ? $backDetails[0]->sale_point : 0);
                }
                $t = $this->collectionToObject($ct);

                $has_bill = true;
                if (in_array($ct->type, [0, 1, 2, 30, 31, 34]) && is_null($billCheck)) {
                    $has_bill = false;
                    $deleted_bills[] = $ct->bill_id;
                }
                $t->has_bill = $has_bill;
                $t->saleProcess = null;
                $t->saleBack = null;
                $t->saleDetails = null;
                $t->backDetails = null;
                if ($ct->type == 1) {
                    $t->saleProcess = $billCheck;
                    $t->saleDetails = $saleDetails;
                    $t->saleBack = null;
                    $t->backDetails = null;
                } else if ($ct->type == 0) {
                    $t->saleProcess = null;
                    $t->saleBack = $billCheck;
                    $t->saleDetails = null;
                    foreach ($backDetails as $bd) {
                        if ($bd->bill_id <= 0) {
                            $bd->price = $bd->price / $bd->quantity;
                        }
                    }
                    $t->backDetails = $backDetails;
                }
                $t->salePoint = $point;
                $user = User::find($ct->user_id);
                $t->user = $user;
                $t->installment_id = $ct->installment_id;

                $collection->push($t);
            });
        });
        $deleted_bills = array_filter($deleted_bills);
        $deleted_bills = array_unique($deleted_bills);

        return [
            'data' => $collection,
            'skip' => $deleted_bills
        ];
    }

    public function updateUnknownClientTransactions($id)
    {
        $c = ClientTransaction::where('client_id', $id)
            ->where('shop_id', auth()->guard('rep')->user()->shop_id)
            ->get();
        foreach ($c as $s) {
            if ($s->pay_day == '0000-00-00') {
                $n_c = ClientTransaction::where('client_id', $s->client_id)
                    ->where('shop_id', auth()->guard('rep')->user()->shop_id)
                    ->where('id', '>', $s->id)
                    ->whereNotIn('type', [30, 31, 34])
                    ->first();
                if (is_null($n_c)) {
                    $n_c = ClientTransaction::where('client_id', $id)
                        ->where('shop_id', auth()->guard('rep')->user()->shop_id)
                        ->where('id', '<', $s->id)
                        ->whereNotIn('type', [30, 31, 34])
                        ->orderBy('pay_day', 'desc')->orderBy('id', 'desc')
                        ->first();
                }
                if (is_null($n_c)) {
                    $n_c = $s;
                }
                $s->pay_day = $n_c->pay_day;
                $s->save();
            }
        }
        if (count($c->sortBy('pay_day')) > 0) {
            $ss = ClientTransaction::where('client_id', $id)
                ->where('shop_id', auth()->guard('rep')->user()->shop_id)
                ->orderBy('pay_day')->first();

            $first = ClientTransaction::where('client_id', $id)
                ->where('shop_id', auth()->guard('rep')->user()->shop_id)
                ->where('type', 4)
                ->first();
            if ($first) {
                $first->pay_day = $ss->pay_day;
                $first->save();
            }
        }
    }

    private function collectionToObject($object)
    {
        $t = $this->transactionFactory->factory($object);
        $t->first_cell = $this->balanceSheetRepo->issuedToHim($object);
        $t->second_cell = $this->balanceSheetRepo->comeFromHim($object);
        $t->transaction_type = $this->transactionType($object->type, $object->bill_id, $object->sale_back_id);
        // dd($t);
        return $t;
    }

    private function transactionType($type, $billId, $backId)
    {
        switch ($type) {
            case '0': //مردود  مبيعات
                $no = $this->getSaleBackBillNo($backId);
                $type = '<a > فاتورة مرتجع بيع رقم ' . $no . '</a>';
                break;
            case '1': // فاتورة بيع
                $no = $this->getSaleBillNo($billId);
                $type = '<a > فاتورة بيع رقم ' . $no . '</a>';
                break;
            case '2': //عملية سداد من العميل
                $type = 'عملية سداد من العميل';

                break;

            case '4': //رصيد جديد
                $type = 'رصيد جديد';
                break;
            case '8': //تعديل مباشر للرصيد
                $type = 'تعديل مباشر للرصيد';
                break;
            case '9': //مردود نقدى للعميل
                $type = 'مردود نقدي للعميل';
                break;

            case '30': //حذف صنف من فاتورة المبيعات
                $no = $this->getSaleBillNo($billId);
                $type = '<a > حذف صنف من فاتورة بيع رقم ' . $no . '</a>';
                break;
            case '31': //إضافة صنف على الفاتورة   // تعديل فاتورة مبيعات
                $no = $this->getSaleBillNo($billId);
                $type = '<a > اضافة صنف الي فاتورة بيع رقم ' . $no . '</a>';
                break;

            case '34': //عديل فاتورة مبيعات
                $no = $this->getSaleBillNo($billId);
                $type = '<a > تعديل فاتورة بيع رقم ' . $no . '</a>';
                break;

            case '60': //خصم رصيد عميل
                $type = " رصيد معدوم ";
                break;

            case '63': //اضافة صنف لفاتورة مرتجع البيع
                $no = $this->getSaleBackBillNo($backId);
                $type = '<a > اضافة صنف الي فاتورة مرتجع بيع رقم ' . $no . '</a>';
                break;
            case '64': //حذف صنف من فاتورة مرتجع البيع
                $no = $this->getSaleBackBillNo($backId);
                $type = '<a > حذف صنف من فاتورة مرتجع بيع رقم ' . $no . '</a>';
                break;

            default:
                $type = "....";
                break;
        }
        return $type;
    }

    private function getSaleBillNo($id)
    {
        $process = SaleProcess::find($id);
        return $process->bill_no ?? '--';
    }

    private function getSaleBackBillNo($id)
    {
        $back = BackProcess::find($id);
        return $back->bill_no ?? '--';
    }

    private function getBackPurchaseBillNo($id)
    {
        $process = SaleProcess::find($id);
        $bill = IncomeBillReturn::find($id);
        return $bill->id ?? $process->bill_no ?? '--';
    }

    private function getPurchaseBillNo($id)
    {
        $back = IncomeBill::find($id);
        return $back->bill_no ?? '--';
    }

}