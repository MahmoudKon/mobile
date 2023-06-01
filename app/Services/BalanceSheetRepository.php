<?php

namespace App\Services;

use App\BackDetails;
use App\BackProcess;
use App\BillAddHistory;
use App\ClientTransaction;
use App\IncomeBill;
use App\IncomeBillReturn;
use App\IncomingDetails;
use App\SaleProcess;

class BalanceSheetRepository
{
    public function calculateBill($bill)
    {
        /*
        $details = SaleDetails::where('shop_id', auth()->guard('rep')->user()->shop_id)
            ->where('sale_id', $bill->id)
            ->get();
        $adds = BillsAddHistory::where('shop_id', auth()->guard('rep')->user()->shop_id)
            ->where('type', 1)
            ->where('bill_id', $bill->id)->sum('addition_value');

        $total = 0;
        $net = 0;
        $discount = $bill->discount;

        foreach ($details as $detail) {
            $t = $detail->price * $detail->quantity;
            $total += $t;
        }
        if ($bill->discount_type == '0') {
            $discount = $total * $discount / 100;
        }
        $net = $total - $discount + $adds;
        $bill->net_price = $net;
        $bill->timestamps = false;
        $bill->save();
        */
    }

    public function calculatePurchaseBill($bill)
    {

        $details = IncomingDetails::where('shop_id', auth()->guard('rep')->user()->shop_id)
            ->where('bill_id', $bill->id)
            ->get();
        $adds = BillAddHistory::where('shop_id', auth()->guard('rep')->user()->shop_id)
            ->where('type', 0)
            ->where('bill_id', $bill->id)->sum('addition_value');
        $total = 0;
        $net = 0;
        $discount = $bill->discount;

        foreach ($details as $detail) {
            $t = $detail->price * $detail->quantity;
            $total += $t;
        }

        if ($bill->discount_type == '0') {
            $discount = $total * $discount / 100;
        }
        $net = $total - $discount + $adds;
        // $bill->net_price = $net;
        $bill->timestamps = false;
        if ($net != $bill->net_price) {
            // var_dump($bill->id, $bill->net_price, $net);
            // $bill->save();
        }
    }

    public function issuedToHim($row)
    {
        if ($row->type == 1) {

            $bill = SaleProcess::find($row->bill_id);
            if ($bill) {
                $this->calculateBill($bill);
                return $bill->net_price;
            }
            return $row->bill_net_total;
            // dd();
            // return $row->amount;
            /*if ($bill && $bill->net_price > 0) {
            return (is_null($bill->net_price) ? "" : $bill->net_price);
            }*/
            if ($row->payment == $row->net_price) {
                return is_null($row->net_price) ? '00' : $row->net_price;
            }
            return is_null($row->bill_net_total) ? $row->net_price : $row->bill_net_total;
        } else if ($row->type == 0) {

            // return $row->amount;
            $bill = BackProcess::find($row->sale_back_id > 0 ? $row->sale_back_id : $row->bill_id);
            return $bill->payment ?? $row->amount;
            // dd();
            if ($bill && $bill->net_price > 0) {
                return (is_null($bill->payment) ? "" : $bill->payment);
            }
        }
        /*
        else if ($row->type == 0 && !is_null($row->safe_balance)) //مردود مبيعات
        {
        //check type of payment
        if ($row->back_stat == 0 && $row->amount == 0) {
        return '';
        }
        return $row->amount;
        } else if ($row->type == 0 && is_null($row->safe_balance)) //مردود مبيعات
        {
        return $row->amount;
        }*/ else if ($row->type == 4 || $row->type == 8) //رصيد ج
        {
            return '';
        } else if ($row->type == 2) //مدقوع من العميل
        {
            return '';
        } else if ($row->type == 9) //مدقوع للعميل
        {
            return $row->amount;
        } else if ($row->type == 30) // حذف صنف
        {
            return '';
        } else if ($row->type == 31 && is_null($row->safe_balance)) // إضافة صنف
        {
            return $row->amount;
        } else if ($row->type == 31 && !is_null($row->safe_balance)) // إضافة صنف
        {
            return $row->amount;
        } else if ($row->type == 34) // تعديل فاتورة
        {
            if ($row->effect == 1) {
                return '';
            }
            return $row->amount;
        } else if ($row->type == 60) //خصم
        {
            return '';
        } else if ($row->type == 111) //تحويل مديونية من عميل المجموعه الي العميل الاساسي للمجموعه
        {
            return $row->amount;
        }
        
        return '';
    }

    public function issuedToHimSupplier($row)
    {

        $order_cells = '';
        if ($row->type == 3) {

            $bill = IncomeBill::find($row->bill_id);
            if ($bill) {
                $this->calculatePurchaseBill($bill);

                $order_cells = (is_null($bill->net_price) ? "00" : $bill->net_price);

                // if ($bill->net_price == 0 && $bill->payment == 0) {
                //     $order_cells = '';
                // }
            }
        } else if ($row->type == 4 || $row->type == 8) {
            $order_cells = '';
        } elseif ($row->type == 5) {
            $bill = IncomeBillReturn::find($row->bill_id);
            if ($bill) {
                $order_cells = (is_null($bill->payment) ? "00" : $bill->payment);
            } else {
                $order_cells = '';
            }
        } elseif ($row->type == 6) {
            $order_cells = '';
            if (!in_array($row->bill_id, ['', null, '0']) && $row->effect == 0) {
                $order_cells = $row->amount;
            }
        } elseif ($row->type == 7) {
            $order_cells = $row->amount;
        } elseif ($row->type == 33) {
            $order_cells = '';
        } else if ($row->type == 32) {
            $order_cells = $row->amount;
        } else if ($row->type == 65 && !is_null($row->safe_balance)) # add item to back put=rchase with cash
        {
            $order_cells = $row->amount;
        } else if ($row->type == 65 && is_null($row->safe_balance)) # add item to back purchase with delay
        {
            $order_cells = '';
        } else if ($row->type == 66 && !is_null($row->safe_balance)) #remove item from back purchase with cash
        {
            $order_cells = $row->amount;
        } else if ($row->type == 66 && is_null($row->safe_balance)) #remove item from back purchase with delay
        {
            $order_cells = $row->amount;
        } else if ($row->type == 61) #remove item from back purchase with delay
        {
            $order_cells = '';
        }
       

        return $order_cells;
    }

    public function comeFromHim($row)
    {
        if ($row->type == 1) {
            $bill = SaleProcess::find($row->bill_id);
            if ($bill) {
                return $bill->payment;
            }
            return $row->bill_net_total;

            return $row->amount;
            $bill = SaleProcess::find($row->bill_id);
            // dd();
            if ($bill && $bill->net_price > 0) {
                return (is_null($bill->payment) ? "" : $bill->payment);
            }
        } /* else if ($row->type == 0 && !is_null($row->safe_balance)) //مردود مبيعات
        {
        //check type of payment
        if ($row->back_stat == 0 && $row->amount == 0) {
        return $row->back_net_price;
        }
        return $row->back_net_price == 0 ? "00" : $row->back_net_price;
        } else if ($row->type == 0 && is_null($row->safe_balance)) //مردود مبيعات
        {
        if ($row->back_stat == 0)
        return '';
        return $row->back_net_price;
        }*/ else if ($row->type == 0) {
            $bill = BackProcess::find($row->sale_back_id > 0 ? $row->sale_back_id : $row->bill_id);
            return $bill->net_price ?? $row->amount;
            if ($bill && $bill->payment == $bill->net_price) {
                return is_null($bill->net_price) ? '00' : $bill->net_price;
            }
            return $row->amount;
            dd($bill);
            return is_null($row->bill_net_total) ? $bill->net_price : $row->bill_net_total;
        } else if ($row->type == 4 || $row->type == 8) //رصيد ج
        {
            return '';
        } else if ($row->type == 2) //مدقوع من العميل
        {
            return $row->amount;
        } else if ($row->type == 9) //مدقوع للعميل
        {
            return '';
        } else if ($row->type == 30) // حذف صنف
        {
            return $row->amount;
        } else if ($row->type == 31 && is_null($row->safe_balance)) // إضافة صنف
        {
            return '';
        } else if ($row->type == 31 && !is_null($row->safe_balance)) // إضافة صنف
        {
            return $row->amount;
        } else if ($row->type == 34) // تعديل فاتورة
        {
            if ($row->effect == 1) {
                return $row->amount;
            }
            return '';
        } else if ($row->type == 60) //خصم
        {
            return $row->amount;
        }
        else if ($row->type == 112) //تصفير مديونية عميل المجموعه لنقل مديونية لعميل المجموعه الاساسي
        {
            return $row->amount;
        }
        return '';
    }

    public function comeFromHimSupplier($row)
    {

        $order_cells = '';
        if ($row->type == 3) {

            $bill = IncomeBill::find($row->bill_id);
            $order_cells = (is_null($bill) ? $row->amount : $bill->payment);

            /*if ($bill && $bill->payment > 0) {
            $order_cells = $bill->payment;
            }*/
            if ($bill && $bill->net_price > 0) {
                //                $order_cells = $bill->net_price;
                $order_cells = (is_null($bill->payment) ?: $bill->payment);
            }

            /* if ($bill && $bill->pay_stat == 1) {
        $order_cells = (is_null($bill->net_price) ? "00" : $bill->net_price);

        if ($bill->net_price == 0 && $bill->payment == 0) {
        $order_cells = '';
        }
        }*/
        } else if ($row->type == 4 || $row->type == 8) {
            $order_cells = '';
        } elseif ($row->type == 5) {

            $bill = IncomeBillReturn::find($row->bill_id);
            if ($bill) {
                $order_cells = (is_null($bill->net_price) ? "00" : $bill->net_price);
            } else {
                $order_cells = '';
            }
        } elseif ($row->type == 6) {
            $order_cells = $row->amount;
        } elseif ($row->type == 7) {
            $order_cells = '';
            if (!in_array($row->bill_id, ['', null, '0']) && $row->effect == 0) {
                $order_cells = $row->amount;
            }
        } elseif ($row->type == 33) {
            $order_cells = $row->amount;
        } else if ($row->type == 32) {
            $order_cells = '';
        } else if ($row->type == 65 && !is_null($row->safe_balance)) # add item to back put=rchase with cash
        {
            $order_cells = $row->amount;
        } else if ($row->type == 65 && is_null($row->safe_balance)) # add item to back purchase with delay
        {
            $order_cells = $row->amount;
        } else if ($row->type == 66 && !is_null($row->safe_balance)) #remove item from back purchase with cash
        {
            $order_cells = $row->amount;
        } else if ($row->type == 66 && is_null($row->safe_balance)) #remove item from back purchase with delay
        {
            $order_cells = '';
        } else if ($row->type == 61) #remove item from back purchase with delay
        {
            $order_cells = $row->amount;
        }

        return $order_cells;
    }

    public function balanceSheet($id, $from, $to, $status)
    {
        $x = ClientTransaction::where('client_transaction.shop_id', auth()->guard('rep')->user()->shop_id)
            ->where('client_transaction.client_id', $id);

        //        $x = $x->whereNotIn('client_transaction.type', [4]);

        if ($status == 'prev') {
            if ($from && !in_array($from, ['', null])) {
                $x = $x->whereDate('pay_day', '<', $from);
            }
        } else {
            if ($from && !in_array($from, ['', null])) {
                $x = $x->whereDate('pay_day', '>=', $from);
            }
            if ($to && !in_array($to, ['', null])) {
                $x = $x->whereDate('pay_day', '<=', $to);
            }
        }

        $x = $x->leftJoin('clients', 'clients.id', 'client_transaction.client_id')
            ->leftjoin('sale_process', 'client_transaction.bill_id', 'sale_process.id')
            ->where(function ($r) {
                $r->whereRaw('client_transaction.is_deleted is null');
                $r->orWhere('client_transaction.is_deleted', '0');
            })
            ->leftjoin('sale_back_invoice', 'client_transaction.sale_back_id', 'sale_back_invoice.id')
            ->select(
                'client_transaction.id',
                'client_transaction.amount',
                'client_transaction.type',
                'client_transaction.pay_day',
                'client_transaction.user_id',
                'client_transaction.safe_point_id',
                // \DB::raw('if (client_transaction.type = 8,DATE(client_transaction.date_time),client_transaction.pay_day) as pay_day') ,
                'client_transaction.balance',
                'client_transaction.bill_id',
                'client_transaction.effect',
                'client_transaction.notes',
                'client_transaction.bill_net_total',
                'client_transaction.installment_id',
                'client_transaction.sale_back_id',
                'is_deleted',
                'clients.client_name',
                'sale_process.net_price',
                'sale_process.bill_no as sale_no',
                'sale_process.pay_stat as sale_state',
                'sale_process.notes as bill_notes',
                'sale_back_invoice.net_price as back_net_price',
                'sale_back_invoice.bill_no as back_id',
                'sale_back_invoice.pay_stat as back_stat',
                'sale_process.payment'
            )
            // ->orderBy('client_transaction.pay_day', 'asc')
            ->orderBy('client_transaction.id', 'asc')
            ->get();
        //            ->paginate(100);

        // dd($x);

        $collection = $x->keyBy('id');

        $sales = [];

        foreach ($collection as $col) {
            if (in_array($col->type, [1, 31, 34])) {
                $bill = SaleProcess::find($col->bill_id);
                if (!$bill || in_array($col->bill_id, $sales)) {
                    $collection->forget($col->id);
                }
                $sales[] = $col->bill_id;
            }
            if (in_array($col->type, [2]) && $col->bill_id > 0) {
                $collection->forget($col->id);
            }
            if (in_array($col->type, [9]) && $col->bill_id > 0) {
                $collection->forget($col->id);
            }
            /*if (in_array($col->type, [0, 64, 63])) {
                $bill = SaleBack::find($col->sale_back_id);
                if (!$bill) {
                    $collection->forget($col->id);
                }
            }*/
            if (in_array($col->type, [0, 64, 63])) {
                // $bill = BackDetails::where('back_id', $col->sale_back_id)->get();

                $bill = BackDetails::join('sale_back_invoice', 'sale_back_invoice.id', 'sale_back.back_id')
                    ->where('back_id', $col->sale_back_id)
                    //                    ->whereRaw('sale_back_invoice.sale_date=sale_back.date_back')
                    ->where('sale_back.client_id', $id)
                    ->where('sale_back_invoice.client_id', $id)
                    ->get();

                // dd($bill, $col->sale_back_id, $col->bill_id);
                if (in_array($col->type, [64, 63])) {
                    $collection->forget($col->id);
                }
                if (count($bill) <= 0) {
                    $collection->forget($col->id);
                }
            }
        }
        return $collection;
    }

    public function balanceSheetSupplier($id, $from, $to, $status)
    {
        $bills = IncomeBill::where('supplier_id', $id)->pluck('id')->toArray();
        $x = ClientTransaction::where('client_transaction.shop_id', auth()->guard('rep')->user()->shop_id)
            ->where('client_transaction.supplier_id', $id)
            ->whereNotIn('client_transaction.type', [32, 33, 70])
            ->whereNotIn('client_transaction.type', [4]);

        if ($status == 'prev') {
            if ($from && !in_array($from, ['', null])) {
                $x->whereDate('pay_day', '<', $from);
            }
        } else {
            if ($from && !in_array($from, ['', null])) {
                $x->whereDate('pay_day', '>=', $from);
            }
            if ($to && !in_array($to, ['', null])) {
                $x->whereDate('pay_day', '<=', $to);
            }
        }
        $x = $x->leftJoin('supplier', 'supplier.id', 'client_transaction.supplier_id')
            ->leftjoin('incoming_bill', function ($q) use ($bills) {
                $q->where('client_transaction.bill_id', 'incoming_bill.id');
                $q->where('client_transaction.type', 3);
            })
            ->leftJoin('incoming_bill_return', function ($join) {
                $join->on('client_transaction.bill_id', 'incoming_bill_return.id');
            })
            ->select(
                'client_transaction.id',
                'client_transaction.amount',
                'client_transaction.type',
                'client_transaction.pay_day',
                'client_transaction.balance',
                'client_transaction.bill_id',
                'client_transaction.effect',
                'client_transaction.notes',
                'client_transaction.safe_balance',
                'supplier.supplier_name',
                'incoming_bill.total_bill',
                'incoming_bill.net_price',
                'incoming_bill_return.net_price as brnet_price',
                'incoming_bill_return.payment as brnet_price2',
                'client_transaction.user_id',
                'client_transaction.safe_point_id',
                'incoming_bill.payment'
            )
            //  ->orderBy('client_transaction.pay_day', 'asc')
            ->orderBy('client_transaction.id', 'asc')
            ->get();

        $collection = $x->keyBy('id');
        foreach ($collection as $col) {
            if (in_array($col->type, [3, 33, 32, 70])) {
                $bill = IncomeBill::find($col->bill_id);
                if (!$bill) {
                    $collection->forget($col->id);
                }
            }

            if (in_array($col->type, [6, 7]) && $col->bill_id > 0) {
                $collection->forget($col->id);
            }

            if ($col->type == 5) {
                $bill = IncomeBillReturn::join('incoming_details_return', 'incoming_bill_return.id', 'incoming_details_return.bill_id')
                    ->join('items', 'items.id', 'incoming_details_return.items_id')->find($col->bill_id);
                if (!$bill) {
                    $collection->forget($col->id);
                }
            }

            if (in_array($col->type, [80, 81, 82, 83])) {
                $collection->forget($col->id);
            }
        }
        return $collection;
    }
}
