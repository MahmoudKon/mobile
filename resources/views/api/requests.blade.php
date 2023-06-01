<!DOCTYPE html>
<html>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">


<link href="{{asset('assets/api/css/bootstrap.css')}}" rel="stylesheet">
<link href="{{asset('assets/api/css/font-awesome.min.css')}}" rel="stylesheet">
<link href="{{asset('assets/api/css/style.css')}}" rel="stylesheet">
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/rateYo/2.3.2/jquery.rateyo.min.css">


<script src="{{asset('assets/api/js/jquery.js')}}"></script>
<script src="{{asset('assets/api/js/bootstrap.js')}}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/rateYo/2.3.2/jquery.rateyo.min.js"></script>


<style>
    label.view {
        padding: 1px
    }

    labe.star {
        font-size: 15px
    }
</style>
<body>

<div id="id01" style="direction: rtl" class="modal" id="showElement">
    <span onclick="close_model()" class="close" title="Close Modal">×</span>
    {!! Form::open([
                       'url'=>'api/v1/'.$shop_id.'/request-rate',

                       'class'=>'modal-content',

			'id' => 'ratingForm',

                       'method'=>'POST',

                       ])!!}
                       
                       {{ csrf_field() }}
                       
    {{--<form class="modal-content ">--}}
    <div style="padding: 5px;">
        
        <div id="set-rate"></div>
        <div id="load-rate"></div>
        
        <label style="float: right!important;  color:#55BFA3;">تعليق</label><br>
        <textarea class="comment" name="comment" placeholder="أضف  تعليقك........."></textarea>
        
        <button type="submit" class="insert">أضف</button>


    </div>
    {!! Form::close()!!}
</div>
<form style="direction: rtl">
    <div class="container" style="padding-top: 30px;">
        <div class="row">

            @forelse($requests as $request)
                <tr style="border-bottom: 1px solid #D6D6D6;">
                    <td>
                        <table style="width: 100%">
                            <tr>
                                <td style="width:40%"> رقم الطلب <span>#{{$request->id}}</span></td>

                                <td style=" background: #F6F6F6 ; width:50%;  text-align: ;">
                                    الاجمالى<span> {{$request->total}} </span>ريال
                                </td>
                            </tr>
                           

                            <tr>
                                <td><strong>التقييم</strong></td>
                                <td>

                                    <?php
                                    $avg = 0;
                                    if (App\Rating::where('order_id', $request->id)->first()) {
                                        $avg = App\Rating::where('order_id', $request->id)->first()->rate_service + App\Rating::where('order_id', $request->id)->first()->rate_order;
                                        $avg = round($avg / 2);
                                    }


                                    $style = "<style>
                    #view-" . $request->id . "-" . $avg . " ~ label.view:before{

                    color: #FD4;

                    }
                    label.view{
                    font-size:15px;
                    }
</style>";
                                    echo $style;
                                    ?>

                                    {{--<div class="rating views" onclick="open_model({{$request->id}})">--}}
                                    <a class="rating views showElementModal"
                                       data-request="{{$request->id}}"
                                       data-toggle="modal"
                                       data-target="#showElement">

                                        <input class="view view-5" id="view-<?php echo $request->id ?>-5" type="radio"
                                               name="view" value="5" disabled @if($avg==5) checked @endif/>

                                        <label class="view view-5" for=view-<?php echo $request->id ?>-5"></label>
                                        <input class="view view-4" id="view-<?php echo $request->id ?>-4" type="radio"
                                               name="view" value="4" disabled @if($avg==4) checked @endif />
                                        <label class="view view-4" for="view-<?php echo $request->id ?>-4"></label>
                                        <input class="view view-3" id="view-<?php echo $request->id ?>-3" type="radio"
                                               name="view" value="3" disabled @if($avg==3) checked @endif/>
                                        <label class="view view-3" for="view-<?php echo $request->id ?>-3"></label>
                                        <input class="view view-2" id="view-<?php echo $request->id ?>-2" type="radio"
                                               name="view" value="2" disabled @if($avg==2) checked @endif/>
                                        <label class="view view-2" for="view-<?php echo $request->id ?>-2"></label>
                                        <input class="view view-1" id="view-<?php echo $request->id ?>-1" type="radio"
                                               name="view" value="1" disabled @if($avg==1) checked @endif/>
                                        <label class="view view-1" for="view-<?php echo $request->id ?>-1"></label>

                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td><a href="{{url('api/v1/'.$shop_id.'/request-details/'.$request->id)}}">
                                        <button type="button" class="edit"
                                                style="border: 1px; border-radius: 3px; margin-bottom: 8px;">
                                            التفاصيل
                                        </button>
                                    </a></td>

                                <td style="width: 90px">
                                    @if($request->status == 1)
                                        <a style="height: 25px; margin-bottom: 6px; margin-right: 10px"
                                           class="btn btn-danger btn-xs"
                                           href="{{url('api/v1/'.$shop_id.'/request-cancel/'.$request->id)}}">
                                            إلغاء الطلب
                                        </a>

                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                    {{--<td><i class="fa fa-trash-o " aria-hidden="true" style="float: left;"></i></td>--}}
                </tr>

            @empty

                <h3 class="text-center"> لا توجد طلبات </h3>


            @endforelse


        </div>
</form>
<script>
    function close_model() {
        document.getElementById('id01').style.display = 'none';
    }


    $('.showElementModal').on('click', function (e) {
        var hashedID = $(this).data('request');

        console.log(hashedID);
        var data = {request_id: hashedID};
        $.ajax({
            url: '{{ route('getRateView') }}',
            type: "GET",
            data: data,
            contentType: false,
            cache: false,
            processData: true, // <==
            timeout: 3000,
            beforeSend: function () {

            },
            success: function (data) {
               console.log(data);
                $('#set-rate').empty().html(data.data);
            },
            error: function (data) {

            },
            complete: function () {
                document.getElementById('id01').style.display = 'block';
            }
        });
    });




            $('#ratingForm').on('submit', function (e) {
            
            
            	var $driver = $("#driver-rate").rateYo();
            	var $service = $("#service-rate").rateYo();
            	var $order = $("#order-rate").rateYo();
            
                e.preventDefault();
                var driver= $driver.rateYo("rating");
                 var service= $service.rateYo("rating");
                  var order= $order.rateYo("rating");

              

                var form = new FormData($("#ratingForm")[0]);

                form.append('order', order);
                form.append('driver', driver);
                form.append('service', service);


                $.ajax({
                    url: "request-rate",
                    type: 'post',
                    data: form,
                    contentType: false,
                    cache: false,
                    processData: false,
                    timeout: 3000,
                    beforeSend: function () {
                    
                        $('#load-rate').empty().append('<span class="fa fa-spinner fa-2x"></span>');
                        
                    },
                    success: function (data) {
                        
                        console.log(data);
                        
                        $('.fa-spinner').remove();
                        
                       
                    },
                    error: function (xhr) {
                        
                        var error = "<ul>";
                        $.each(xhr.responseJSON, function (index, value) {
                            error += '<li>' + value + '</li>';
                        });
                        error += "</ul>";
                        
                        $('#load-rate').empty().append(error);
                        
                    },
                    complete: function () {
                    
			close_model();
			location.reload();
                    }
                });

            });

</script>
</body>
</html>
