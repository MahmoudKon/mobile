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
                                <th> الحد الادنى</th>
                                <th>الكميه</th>

                            </tr>
                            @foreach($items as $item)
                                <tr>
                                    @if($item->store_quant <= $item->min_quantity)
                                        <td style="background:#E9E9E9;">{{$item->item_name}}</td>
                                        <td style="background:#F7F7F7">{{$item->min_quantity}}</td>
                                        <td style="background:#F7F7F7">{{$item->store_quant}}</td>
                                    @else

                                    @endif

                                </tr>
                            @endforeach

                        </table>
                    @else
                        <h1>لا يوجد نواقص</h1>
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
