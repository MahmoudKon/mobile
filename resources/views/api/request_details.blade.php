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
    {!! Form::open([
                      'url'=>'api/v1/'.$shop_id.'/request-details-rate',

                      'class'=>'modal-content',


                      'method'=>'POST',

                      ])!!}

    <div style="padding: 5px;">
        <label style="color:#55BFA3">قيم المنتج</label>
        <div style="clear: both;">
            <div class="rating stars">
                <input class="star star-5" id="star-5" type="radio" name="star" value="5"/>
                <label class="star star-5" for="star-5"></label>
                <input class="star star-4" id="star-4" type="radio" name="star" value="4"/>
                <label class="star star-4" for="star-4"></label>
                <input class="star star-3" id="star-3" type="radio" name="star" value="3"/>
                <label class="star star-3" for="star-3"></label>
                <input class="star star-2" id="star-2" type="radio" name="star" value="2"/>
                <label class="star star-2" for="star-2"></label>
                <input class="star star-1" id="star-1" type="radio" name="star" value="1"/>
                <label class="star star-1" for="star-1"></label>
            </div>
        </div>
        <label style="float: right!important;  color:#55BFA3;">تعليق</label><br>
        <textarea class="comment" placeholder="أضف  تعليقك........."></textarea>
        <input type="hidden" name="requestId" id="requestId">
        <button type="submit" class="insert">أضف</button>
        </td>

    </div>
    {!! Form::close()!!}
</div>
<form style="direction: rtl">
    <div style="text-align: center !important;">
        <table class="req_details">
            <tr>
                <td> رقم الطلب :</td>
                <td><span>{{$request->id}}</span></td>
                <td> التاريخ :</td>
                {{--                <td> <span>{{date('Y-m-d',$request->created_at )}}</span> </td>--}}
                <td><span>{{\Carbon\Carbon::parse($request->created_at)->format('Y-m-d') }}</span></td>
            </tr>
            <tr>
                @if($request->status==2)
                    <td>رقم الفاتورة :</td>
                    <td><span>{{$request->saleProcess->bill_no}}</span></td>
                @endif
                <td> حالة الطلب :</td>
                <td><span>{{$request->status_display}}</span></td>
            </tr>
            <tr>

                <td colspan="4" class="details" style="text-align: center">
                    @if(count($items)>0)     
                    <table style="width: 100%">
                        <tr>
                            <th>الصنف</th>
                            <th> الكمية</th>
                            <th>المبلغ</th>
                            <th>الاجمالى</th>

                        </tr>
                 
                        @foreach($items as $item)
                            <tr>
                                <td style="background:#E9E9E9;">@if($item->item){{$item->item->item_name}}@endif</td>
                                <td style="background:#F7F7F7">{{$item->quantity}}</td>
                                <td style="background:#F7F7F7">{{$item->price}}</td>
                                <td style="background:#F7F7F7">{{$item->quantity*$item->price}}</td>

                        @endforeach

                        <tr>
                            <td colspan="2" style="background:#F0F0F0;">المجموع</td>
                            <td colspan="2" style="background:#F7F7F7">{{$request->total}}</td>
                        </tr>
                    </table>
                     @endif
                </td>
            </tr>

        </table>
    </div>
</form>


{{--<div class="stars col-md-6 pull-right">--}}
{{--<label class="col-md-3 "> Rate &nbsp;</label>--}}
{{--&nbsp;&nbsp;&nbsp;--}}

{{--<form action="">--}}
{{--<input class="star star-5" id="star-5" type="radio" name="star" value="5"  />--}}
{{--<label class="star star-5" for="star-5"></label>--}}
{{--<input class="star star-4" id="star-4" type="radio" name="star" value="4" />--}}
{{--<label class="star star-4" for="star-4"></label>--}}
{{--<input class="star star-3" id="star-3" type="radio" name="star" value="3" />--}}
{{--<label class="star star-3" for="star-3"></label>--}}
{{--<input class="star star-2" id="star-2" type="radio" name="star" value="2" />--}}
{{--<label class="star star-2" for="star-2"></label>--}}
{{--<input class="star star-1" id="star-1" type="radio" name="star" value="1" />--}}
{{--<label class="star star-1" for="star-1"></label>--}}

{{--<div class="clearfix"></div>--}}



{{--<div class="clearfix"></div>--}}
{{--<button type="submit" class=" btn btn-send pull-left"> Comment <i--}}
{{--class="fa fa-paper-plane"--}}
{{--aria-hidden="true"></i></button>--}}

{{--</form>--}}
{{--</div>--}}


<script>
    function close_model() {
        document.getElementById('id02').style.display = 'none';
    }

    function open_model(id) {
        $("#requestId").val(id);
        document.getElementById('id02').style.display = 'block';
    }

    $(".favourit_icon").click(function () {
        $(this).css({"color": "#FDA517"}).removeClass('fa-star-o').addClass('fa-star');
    });
</script>
</body>
</html>
