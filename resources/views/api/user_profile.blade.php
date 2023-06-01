<!DOCTYPE html>
<html>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="{{asset('assets/api/css/style.css')}}" rel="stylesheet">
<body>


{{--@inject('city','App\City')--}}

<?php
//$cities = $city->where('city_up',0)->pluck('city_name', 'id');
//$cities = $city->pluck('city_name', 'id');
//$cities = $city->get();
?>

{{--<form style="direction: rtl">--}}
@include('flash::message')
{!! Form::model($model,[
                       'url'=>'api/v1/'.$shop_id.'/user-profile',
                       'id'=>'myForm',
                       'role'=>'form',
                       'style'=>'direction: rtl',
                       'method'=>'POST',
                        'files'=>'true'
                       ])!!}
<input type="hidden" name="api_token" value="{{$model->api_token}}">
<div style="text-align: center;">
    
    <table class="regist">
        <tr>
            <td><label>اسم المندوب </label></td>
            <td>
                {{Form::text('name',null,['id'=>'name_1','placeholder'=>'أدخل اسم المندوب '])}}

            </td>
        </tr>

        <tr>
            <td><label>اسم المستخدم </label></td>
            <td>
                {{Form::text('user_name',null,['id'=>'name_1','placeholder'=>'أدخل اسم المستخدم '])}}

            </td>
        </tr>



        <tr>
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
