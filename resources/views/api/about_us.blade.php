<!DOCTYPE html>
<html>
<head>
    <title> {{ $shop->shop_name }} </title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="{{asset('assets/api/css/bootstrap.css')}}" rel="stylesheet">
    <link href="{{asset('assets/api/css/font-awesome.min.css')}}" rel="stylesheet">
    <link href="{{asset('assets/api/css/style.css')}}" rel="stylesheet">
    <script src="{{asset('assets/api/js/jquery.js')}}"></script>
    <script src="{{asset('assets/api/js/bootstrap.js')}}"></script>
</head>
<body>
<div class="container">


    <h1 class="text-center">عن {{ $shop->shop_name }}  </h1>

    {{--<div class="about-content text-center">--}}
        {!!  $shop->about !!} 
    {{--</div>--}}


    {{--<div id="content">--}}
        {{--<ul>--}}
            {{--<li id="top_content"><a id="paragraph_d" class="min" href="https://colorslab.com/textgator/"></a>--}}
                {{--<div id="content">--}}
                    {{--<div id="top_content"></div>--}}
                    {{--<div id="full_content">--}}
                        {{--<div id="text"><p><span style="background-color: #3366ff;">هذا النص هو مثال لنص يمكن أن يستبدل في نفس المساحة، لقد تم توليد هذا النص من مولد النص العربى، حيث يمكنك أن تولد مثل هذا النص أو العديد من النصوص الأخرى إضافة إلى زيادة عدد الحروف التى يولدها التطبيق.</span><br--}}
                                    {{--class="line-break"/><span style="background-color: #3366ff;">إذا كنت تحتاج إلى عدد أكبر من الفقرات يتيح لك مولد النص العربى زيادة عدد الفقرات كما تريد، النص لن يبدو مقسما ولا يحوي أخطاء لغوية، مولد النص العربى مفيد لمصممي المواقع على وجه الخصوص، حيث يحتاج العميل فى كثير من الأحيان أن يطلع على صورة حقيقية لتصميم الموقع.</span><br--}}
                                    {{--class="line-break"/><span style="background-color: #3366ff;">ومن هنا وجب على المصمم أن يضع نصوصا مؤقتة على التصميم ليظهر للعميل الشكل كاملاً،دور مولد النص العربى أن يوفر على المصمم عناء البحث عن نص بديل لا علاقة له بالموضوع الذى يتحدث عنه التصميم فيظهر بشكل لا يليق.</span><br--}}
                                    {{--class="line-break"/><span style="background-color: #3366ff;">هذا النص يمكن أن يتم تركيبه على أي تصميم دون مشكلة فلن يبدو وكأنه نص منسوخ، غير منظم، غير منسق، أو حتى غير مفهوم. لأنه مازال نصاً بديلاً ومؤقتاً.</span>--}}
                            {{--</p></div>--}}
                    {{--</div>--}}
                {{--</div>--}}
            {{--</li>--}}
        {{--</ul>--}}
    {{--</div>--}}
</div>
</body>
{{--<script>--}}

{{--var txt = '{{ $shop->about }}';--}}

{{--console.log(txt);--}}
{{--txt = txt.replace(/\r\n/g, '<br />');--}}
{{--$('.about-content').html(txt);--}}

{{--</script>--}}
</html>
