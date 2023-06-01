<!DOCTYPE html>
<html>
<head>
    {{--<title>سلتي | اتصل بنا</title>--}}
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">


    <link href="{{asset('assets/api/css/bootstrap.css')}}" rel="stylesheet">
    <link href="{{asset('assets/api/css/font-awesome.min.css')}}" rel="stylesheet">
    <link href="{{asset('assets/api/css/style.css')}}" rel="stylesheet">
    <script src="{{asset('assets/api/js/jquery.js')}}"></script>
    <script src="{{asset('assets/api/js/bootstrap.js')}}"></script>
    <style>
        @import url(http://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.min.css);

        body {
            margin-top: 20px
        }

        .fa {
            font-size: 24px;
            margin-right: 5px
        }

        .row-first {
            margin-bottom: 10px;
            margin-top: 8px
        }

        .title-contact {
            margin-top: 32px;
            display: none;
        }

        .contact-email {
            display: none;
        }

        a {
            transition: all .3s ease;
            -webkit-transition: all .3s ease;
            -moz-transition: all .3s ease;
            -o-transition: all .3s ease
        }

        .quick-contact {
            color: #fff;
            background-color: #1AAA55;
            text-align: center
        }

        .contact a {
            -webkit-border-radius: 2px;
            -moz-border-radius: 2px;
            -o-border-radius: 2px;
            border-radius: 2px;
            display: block;
            background-color: rgba(255, 255, 255, 0.25);
            font-size: 20px;
            text-align: center;
            color: #fff;
            padding: 7px
        }

        .contact a:hover {
            background-color: rgba(255, 255, 255, 0.85);
            text-decoration: none
        }

        .contact a.skype:hover, .fa-skype {
            color: #00aff0
        }

        .contact a.google:hover, .fa-google-plus {
            color: #dd4b39
        }

        .contact a.linkedin:hover, .fa-linkedin {
            color: #0e76a8
        }

        .contact a.twitter:hover, .fa-twitter {
            color: #00acee
        }

        .jumbotron {
            background: #1AAA55;
            color: #FFF;
            border-radius: 0px;
        }

        .jumbotron-sm {
            padding-top: 24px;
            padding-bottom: 24px;
        }

        .jumbotron small {
            color: #FFF;
        }

        .h1 small {
            font-size: 24px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="container">
        <div class="jumbotron jumbotron-sm">
            <div class="container">
                <div class="row">
                    <div class="col-sm-12 col-lg-12">
                        <h2 class="pull-right">
                            نرحب بتواصلك دائماً
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        @if(isset($message))
            <div class="alert alert-success">
                {{ var_dump($message) }}
            </div>
        @endif
        <form action="{{url('api/v1/'.request()->segment(3).'/contact-us')}}" method="POST">
            {{csrf_field()}}
            <div class="row">
                <div class="col-md-12">
                    <div class="well well-sm">

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name" class="pull-right">
                                            الرسالة</label>
                                        <textarea dir="rtl" name="complaint" id="message" class="form-control" rows="9"
                                                  cols="25" required="required"
                                                  placeholder="الرسالة"></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name" class="pull-right">
                                            الاسم</label>
                                        <input name="complainant" type="text" dir="rtl" class="form-control" id="name" placeholder="ادخل اسم"
                                               required="required"/>
                                    </div>
                                    <div class="form-group">
                                        <label for="name" class="pull-right">
                                            رقم الهوية</label>
                                        <input name="identity" type="text" dir="rtl" class="form-control" id="email"
                                               placeholder="ادخل رقم الهوية" required="required"/>
                                    </div>
                                    <div class="form-group">
                                        <label for="name" class="pull-right">
                                            الجوال </label>
                                        <input name="phone" type="text" dir="rtl" class="form-control" id="email"
                                               placeholder="ادخل رقم الجوال" required="required"/>
                                    </div>
                                </div>
                                @include('errors.validation-errors')
                                <div class="col-md-12">
                                    <button onclick="sendMsg()" type="submit" class="btn btn-success pull-left" id="btnContactUs">
                                        إرسال
                                    </button>
                                </div>
                            </div>

                    </div>
                </div>

            </div>

        </form>

        <div class="well well-sm quick-contact">
            <div class="row">
                <div class="row">
                    <div class="col-md-4 col-md-offset-4">
                        <h3 class="text-center">
                            اتصل بنا</h3>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4"><h4>{{$setting->email}} <i style="font-size: 30px" class="fa fa-envelope" aria-hidden="true"></i></h4></div>
                    <div class="col-md-4"><h4>{{$setting->telephone}} <i style="font-size: 30px" class="fa fa-mobile" aria-hidden="true"></i></h4></div>
                    <div class="col-md-4">
                        <h4>{{$setting->address}} <i style="font-size: 30px" class="fa fa-map-marker" aria-hidden="true"></i></h4></div>


                </div>

            </div>
        </div>
    </div>

</div>
<script>
    $(document).ready(function () {
        $(".title-contact, .contact-email").fadeIn("slow");
    });

    function sendMsg()
    {
        var identity = $("input[name='identity']").val();
        var complainant = $("input[name='complainant']").val();
        var phone = $("input[name='phone']").val();
        var complaint = $("input[name='complaint']").val();
        if(identity != '' && complainant != '' && phone !='' && complaint != '')
        {
            alert('جاري إرسال الشكوى سيتم مراجعتها من قبل الإدارة ومعاودة الاتصال بك');
        } else {
            alert('من فضلك قم بإملاء جميع الحقول')
        }
    }
</script>
</body>
</html>
