<?php


function isActiveRoute($route, $output = 'active')
{
    if (Route::currentRouteName() == $route) {
        return $output;
    }
}

function price_decimal($item, $shop_id = null)
{
    if(is_null($shop_id))
    {
        $shop_id = auth()->guard('rep')->user()->shop_id;
    }
    $p_scale = \DB::table('badr_shop')->where('serial_id', $shop_id)->first()->decimal_num_price;
    return bcdiv($item, 1, $p_scale);
}
function price_decimal_rest($item, $shop_id)
{

    $p_scale = \DB::table('badr_shop')->where('serial_id', $shop_id)->first()->decimal_num_price;
    $aa= round($item,  $p_scale, PHP_ROUND_HALF_UP);
    return bcdiv($aa, 1, $p_scale);
}

function quant_decimal($item, $shop_id)
{
    $q_scale = \DB::table('badr_shop')->where('serial_id', $shop_id)->first()->decimal_num_quant;
    return bcdiv($item, 1, $q_scale);
}
function similarWords($text)
{
    $replace = [
        "أ",
        "ا",
        "إ",
        "آ",
        "ي",
        "ى",
        "ه",
        "ة",
        "٩",
        "٨",
        "٧",
        "٦",
        "٥",
        "٤",
        "٣",
        "٢",
        "١",
        "٠",
        "9",
        "8",
        "7",
        "6",
        "5",
        "4",
        "3",
        "2",
        "1",
        "0",
    ];
    $with = [
        "(أ|ا|آ|إ)",
        "(أ|ا|آ|إ)",
        "(أ|ا|آ|إ)",
        "(أ|ا|آ|إ)",
        "(ي|ى)",
        "(ي|ى)",
        "(ه|ة)",
        "(ه|ة)",
        "(9|٩)",
        "(8|٨)",
        "(7|٧)",
        "(6|٦)",
        "(5|٥)",
        "(4|٤)",
        "(3|٣)",
        "(2|٢)",
        "(1|١)",
        "(0|٠)",
        "(9|٩)",
        "(8|٨)",
        "(7|٧)",
        "(6|٦)",
        "(5|٥)",
        "(4|٤)",
        "(3|٣)",
        "(2|٢)",
        "(1|١)",
        "(0|٠)"
    ];

    $new = array_combine($replace, $with);
    $return = "";
    $len = strlen(utf8_decode($text));
    for ($i = 0; $i < $len; $i++) {
        $current = mb_substr($text, $i, 1, 'utf-8');
        if (isset($new[$current])) {
            $return .= $new[$current];
        } else {
            $return .= $current;
        }
    }
    return $return;
}

function balance($col, $pre_balance)
{
    $first_col = (float)$col->first_cell;
    $second_col = (float)$col->second_cell;
    $balance = $pre_balance + $first_col - $second_col;
    return $balance;
}