<?php

namespace App\Listeners;

use App\Events\EmailBillEvent;
use App\Item;
use App\RequestAddHistory;
use App\SaleDetails;
use App\SaleProcess;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class BillEventListener
{

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     *
     * @param  EmailEvent  $event
     * @return void
     */
    public function handle(EmailBillEvent $event)
    {
        $email = $event->data['email'];
        $name = $event->data['name'];
        $b_id = $event->data['bill_id'];
        $shop_id = $event->data['shop_id'];

        $data = $this->createMailData($b_id, $shop_id);

        \Mail::send('bill-mail', ['data' => $data, 'sub' => $event->data], function ($m) use ($email, $name) {
            $m->from('card-net@hotmail.com', 'CardNet');
            $m->to($email, $name)->subject('Order Success');
        });
    }

    public function createMailData($id, $shop_id){

        $bill = SaleProcess::find($id);

        $adds = RequestAddHistory::where('bill_id', $id)
            ->where('shop_id', $shop_id)
            ->select('addition_id', 'addition_value')
            ->get();

        foreach ($adds as $add) {
            $aa = \DB::table('bills_add')->where('id', $add->addition_id)->first();
            if ($aa) {
                $aa_name = $aa->Addition_name;
            } else {
                $aa_name = '--';
            }
            $add->name = $aa_name;
        }

        $details = SaleDetails::where('shop_id', $shop_id)
            ->where('sale_id', $id)
            ->select('items_id', 'sale_id', 'id', 'total_price', 'quantity', 'date_sale', 'item_name', 'unit', 'price')
            ->get();

        foreach ($details as $detail){
            $item = Item::find($detail->items_id);
            $detail->cards = $item->getCards($id) ?? [];
        }
        return compact('bill', 'details', 'adds');
    }


}
