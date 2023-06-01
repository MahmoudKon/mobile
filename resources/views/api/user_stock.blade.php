<!DOCTYPE html>
<html>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="{{asset('assets/api/css/bootstrap.css')}}" rel="stylesheet">
<link href="{{asset('assets/api/css/font-awesome.min.css')}}" rel="stylesheet">
<link href="{{asset('assets/api/css/style.css')}}" rel="stylesheet">
<script src="{{asset('assets/api/js/jquery.js')}}"></script>
<script src="{{asset('assets/api/js/bootstrap.js')}}"></script>
<body>
<div id="id02" style="direction: rtl" class="modal">
    <span onclick="close_model()" class="close" title="Close Modal">×</span>
    <form class="modal-content ">
        <div style="padding: 5px;">
            <label style="color:#55BFA3">قيم المنتج</label>
            <div class="rating">
                <i class="fa fa-star-o favourit_icon" aria-hidden="true"></i>
                <i class="fa fa-star-o favourit_icon" aria-hidden="true"></i>
                <i class="fa fa-star-o favourit_icon" aria-hidden="true"></i>
                <i class="fa fa-star-o favourit_icon" aria-hidden="true"></i>
                <i class="fa fa-star-o  favourit_icon" aria-hidden="true"></i>
            </div>
            <label style="float: right!important;  color:#55BFA3;">تعليق</label><br>
            <textarea class="commet" placeholder="أضف  تعليقك........."></textarea>
            <button type="button" class="insert">أضف</button>
            </td>

        </div>
    </form>
</div>
<form style="direction: rtl">
    <div style="text-align: center !important;">
        <table class="req_details">

            <tr>
                <td colspan="4" class="details" style="text-align: center">
                    @if(count($items)>0)
                        <table style="width: 100%">


                            <tr>
                                <th>الصنف</th>
                                <th> الكمية</th>
                                <th>المبلغ</th>
                                <th>اجمالى</th>
                            </tr>
                            @foreach($items as $item)
                                <tr>
                                    <td style="background:#E9E9E9;">{{$item->item_name}}</td>
                                    <td style="background:#F7F7F7">{{$item->store_quant}}</td>
                                    <td style="background:#F7F7F7">{{$item->sale_price}}</td>
                                    <td style="background:#F7F7F7">{{$item->total}}</td>

                                </tr>
                            @endforeach
                            <tr>
                                <td colspan="1" style="background:#F0F0F0;">الاجمالى</td>
                                <td colspan="2" style="background:#F7F7F7">{{$total_quantity}}</td>
                                <td colspan="1" style="background:#F7F7F7">{{$total_price}}</td>
                            </tr>
                        </table>
                    @else
                        <h1>لا يوجد مخزون</h1>
                    @endif
                </td>
            </tr>

        </table>
    </div>
</form>
<script>
    function close_model() {
        document.getElementById('id02').style.display = 'none';
    }

    function open_model() {
        document.getElementById('id02').style.display = 'block';
    }

    $(".favourit_icon").click(function () {
        $(this).css({"color": "#FDA517"}).removeClass('fa-star-o').addClass('fa-star');
    });
</script>
</body>
</html>
