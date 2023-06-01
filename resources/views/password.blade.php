<!DOCTYPE html>
<html>
    <head>
        <title>Alian</title>

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
                <div class="title">مرحبا, {{ $data['name'] }}</div>
                <div class="content">كود استعادة كلمة المرور هو: </div>
                <div class="content">{{ $data['code'] }}</div>
            </div>
        </div>
    </body>
</html>