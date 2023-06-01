<!DOCTYPE html>
<html>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="{{asset('assets/api/css/bootstrap.css')}}" rel="stylesheet">
<link href="{{asset('assets/api/css/style.css')}}" rel="stylesheet">
<body>


{{--@inject('city','App\City')--}}

<?php
//$cities = $city->where('city_up',0)->pluck('city_name', 'id');
//$cities = $city->pluck('city_name', 'id');
//$cities = $city->get();
?>

{{--<form style="direction: rtl">--}}
<!--@include('flash::message')-->

@if($response)
    @if(count($response) > 0)
        @if($response['status'] == '1')
            <div class="alert alert-success">
                @foreach($response['msg'] as $item)
                    <ul class="list-unstyles">
                        {{ $item }}
                    </ul>
                @endforeach
            </div>
         @else
            <div class="alert alert-danger">
                @foreach($response['msg'] as $item)
                    <ul class="list-unstyles">
                        {{ $item }}
                    </ul>
                @endforeach
            </div>
        @endif
    @endif
@endif

{!! Form::model($model,[
                       'url'=>'api/v1/'.$shop_id.'/profile',
                       'id'=>'myForm',
                       'role'=>'form',
                       'style'=>'direction: rtl',
                       'method'=>'POST',
                        'files'=>'true'
                       ])!!}
<input type="hidden" name="api_token" value="{{$model->api_token}}">
<div style="text-align: center;">

    <table class="regist">
        <tr >
            <td><label>الاسم </label></td>
            <td>
                {{Form::text('client_name',$model->client_name,['id'=>'name_1','placeholder'=>'أدخل الاسم'])}}

            </td>
        </tr>
        <tr class="">
            <td><label>رقم الجوال </label></td>
            <td>
                {{Form::text('mobile1',$model->tele,['id'=>'phone_1','placeholder'=>'أدخل رقم الجوال ','onkeypress'=>'return event.charCode >= 48 && event.charCode <= 57'])}}

            </td>
        </tr>
         <tr class="">
            <td><label>نقاطي </label></td>
            <td>
                {{Form::text('gift_points',null,['id'=>'gift_points','readonly'=>'readonly'])}}

            </td>
        </tr>
        <!--<tr class="">-->
        <!--    <td><label>البريد الالكنرونى </label></td>-->
        <!--    <td>-->
        <!--        {{Form::email('email',null,['id'=>'email_1','placeholder'=>'أدخل البريد الالكنرونى ','onblur'=>'validateEmail(this);'])}}-->


        <!--    </td>-->
        <!--</tr>-->
        <tr class="">
            <td><label>كلمة المرور</label></td>
            <td>
                {{Form::password('password',['id'=>'psw','placeholder'=>'أدخل كلمة المرور'])}}
                {{--<input type="password" placeholder="أدخل كلمة المرور" name="psw" id="psw">--}}
            </td>
        </tr>
        <tr>
            <td><label>تاكيد كلمة المرور</label></td>
            <td>
                {{Form::password('password_confirmation',['id'=>'psw-repeat','placeholder'=>'تاكيد كلمة المرور'])}}
                {{--<input type="password" placeholder="تاكيد كلمة المرور" name="psw-repeat" id="psw-repeat">--}}
            </td>
        </tr>
        
        

      
        <tr>
            <td colspan="2">
                <button type="submit" onclick="save()">تعديل</button>
            </td>
        </tr>
    </table>
</div>
{!! Form::close()!!}

<script src="{{asset('assets/api/js/jquery.js')}}"></script>
<script>
    //    $(function() {
    //        $("#id1 option[cityup !='0' ]").hide();
    //        $("#id2 option").hide();
    //        $("#id1").change( function(){
    //            var id=$(this).val();
    //            $("#id2 option").hide();
    //            $("#id2 option[cityup ='"+id+"']").show();
    //             });
    //    });


    $(document).ready(function () {
        $('#parent').on('change', function (e) {
//            $('#parent').empty();
            $('#son').empty();

            $('#son').append('<option value="">اختار اسم المدينه</option>');

            var st = e.target.value;

            $.get('{{url("address/city") }}?st=' + st, function (data) {

                $.each(data, function (index, stateObj) {
                    $('#son').append('<option value="' + stateObj.id + '">' + stateObj.city_name + '</option>');
                });
            });
        });

        {{--$('#state_id').on('change', function (e) {--}}
        {{--$('#city_id').empty();--}}
        {{--$('#city_id').append('<option value="">اختار اسم المدينه</option>');--}}

        {{--var st = e.target.value;--}}

        {{--$.get('{{url("address/city") }}?st=' + st, function (data) {--}}

        {{--$.each(data, function (index, cityObj) {--}}
        {{--$('#city_id').append('<option value="' + cityObj.id + '">' + cityObj.name + '</option>');--}}
        {{--});--}}
        {{--});--}}
        {{--});--}}


    });


    function validateEmail(emailField) {
        var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;

        if (reg.test(emailField.value) == false) {
            document.getElementById("email_1").focus();
            return false;
        }

        return true;

    }

    function save() {
        var name_1 = document.getElementById("name_1").value;
        var phone_1 = document.getElementById("phone_1").value;
        var email_1 = document.getElementById("email_1").value;
        var psw = document.getElementById("psw").value;
        var p_repeat = document.getElementById("psw-repeat").value;
        if (name_1 == '') {
            document.getElementById("name_1").focus();
            return false;
        }
        if (phone_1 == '') {
            document.getElementById("phone_1").focus();
            return false;
        }
        if (email_1 == '') {
            document.getElementById("email_1").focus();
            return false;
        }
        if (psw == '') {
            document.getElementById("psw").focus();
            return false;
        }
        if (p_repeat == '') {
            document.getElementById("psw-repeat").focus();
            return false;
        }
    }
</script>
</body>
</html>
