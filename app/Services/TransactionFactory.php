<?php

namespace App\Services;

class TransactionFactory
{
    public function factory($object)
    {
        $t = new \stdClass();
        $t->client_id = $object->client_id;
        $t->id = $object->id;
        $t->amount = $object->amount;
        $t->type = $object->type;
        $t->pay_day = $object->pay_day;
        $t->balance = $object->balance;
        $t->bill_id = $object->bill_id;
        $t->effect = $object->effect;
        $t->notes = $object->notes;
        $t->bill_net_total = $object->bill_net_total;
        $t->client_name = $object->client_name;
        $t->net_price = $object->net_price;
        $t->bill_no = $object->bill_no;
        $t->back_net_price = $object->back_net_price;
        $t->safe_balance = $object->safe_balance;
        $t->back_stat = $object->back_stat;
        $t->sale_back_id = $object->sale_back_id;
        $t->payment = $object->payment;
        return $t;
    }


    public function factorySupplier($object)
    {
        $t = new \stdClass();
        $t->supplier_id = $object->supplier_id;
        $t->id = $object->id;
        $t->amount = $object->amount;
        $t->type = $object->type;
        $t->pay_day = $object->pay_day;
        $t->balance = $object->balance;
        $t->bill_id = $object->bill_id;
        $t->effect = $object->effect;
        $t->notes = $object->notes;
        $t->bill_net_total = $object->bill_net_total;
        $t->client_name = $object->client_name;
        $t->net_price = $object->net_price;
        $t->bill_no = $object->bill_no;
        $t->back_net_price = $object->back_net_price;
        $t->safe_balance = $object->safe_balance;
        $t->back_stat = $object->back_stat;
        $t->sale_back_id = $object->sale_back_id;
        $t->payment = $object->payment;
        return $t;
    }
}
