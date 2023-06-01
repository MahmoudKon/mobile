<!DOCTYPE html>
<html>
<head>
    <title>Order Success</title>

    <style>
        html, body {
            height: 100%;
        }

        body {
            margin: 0;
            padding: 0;
            width: 100%;
            display: table;
            font-weight: 100;
            font-family: 'Lato';
        }

        .container {
            text-align: center;
            display: table-cell;
            vertical-align: middle;
        }

        .content {
            text-align: center;
            display: inline-block;
            font-size: 26px;
        }

        .title {
            font-size: 46px;
        }
    </style>
</head>
<body style="direction: rtl !important; " align="center">
<div class="container">
    <div class="content">
        <div class="title">مرحبا, {{ $sub['name'] }}</div>
        <div class="content">
            <b>تم انشاء فاتورة  ورقمها: {{ $data['bill']->bill_no }}</b>
            <br>
            <table border="1" cellpadding="0" cellspacing="0" width="100%"
                   class="table table-bordered">
                <thead style="font-size: 20px !important;">
                <tr class="info">
                    <th>الصنف</th>
                    <th>الكمية</th>
                    <th>تفاصيل اضافية</th>
                    <th>سعر الوحدة</th>
                    <th>الاجمالي</th>
                </tr>
                </thead>
                <tbody style="font-size: 20px !important;">
                @foreach($data['details'] as $item)
                    <tr>
                        <td class="text-center"> {{ $item->getName() }}</td>
                        <td class="text-center">{{ quant_decimal($item->quantity, $sub['shop_id']) }}</td>
                        <td class="text-center">
                            <ul>
                                @foreach($item->cards as $card)
                                    <li>{{ $card }}</li>
                                @endforeach
                            </ul>

                        </td>
                        <td class="text-center">{{ price_decimal($item->price, $sub['shop_id']) }}</td>
                        <td class="text-center">{{ price_decimal($item->price * $item->quantity, $sub['shop_id']) }}</td>
                    </tr>
                @endforeach


                <tr style="background: #b8daff; color: black; font-size: 14px;">
                    <td colspan="3">اجمالي الفاتورة</td>
                    <td colspan="2">{{ price_decimal($data['bill']->total_price, $sub['shop_id']) }}</td>
                </tr>

                <tr style="background: #ffe38f; color: black; font-size: 14px;">
                    <td colspan="3">الخصم</td>
                    <td colspan="2">{{ price_decimal($data['bill']->discount, $sub['shop_id']) }}</td>
                </tr>

                @foreach($data['adds'] as $add)
                    <tr style="background: #baffa5; color: black; font-size: 14px;">
                        <td colspan="3">{{ $add->name }}</td>
                        <td colspan="2">{{ price_decimal($add->addition_value, $sub['shop_id']) }}</td>
                    </tr>
                @endforeach
                <tr style="background: #c6fff8; color: black; font-size: 14px;">
                    <td colspan="3">صافي الفاتورة</td>
                    <td colspan="2">{{ price_decimal($data['bill']->net_price, $sub['shop_id']) }}</td>
                </tr>


                </tbody>

            </table>


        </div>
    </div>
</div>
</body>
</html>
