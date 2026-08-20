<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: safety, dejavusans, arial, tahoma, sans-serif;
            font-size: 9pt;
            color: #000;
            direction: rtl;
            margin: 0;
            padding: 0;
        }

        .classification {
            font-size: 8pt;
            color: #548235;
            margin: 0 0 8px 0;
            line-height: 1.3;
        }

        .classification .en {
            direction: ltr;
            unicode-bidi: embed;
        }

        .frame {
            border: 1px solid #000;
            padding: 10px;
            width: 100%;
        }

        table.gallery {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.gallery td {
            width: 50%;
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            vertical-align: middle;
            height: 105mm;
            overflow: hidden;
        }

        .ev-img {
            max-width: 96%;
            max-height: 98mm;
            width: auto;
            height: auto;
        }

        .footer-class {
            font-size: 8pt;
            color: #548235;
            margin-top: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
@php
    $slots = array_values($evidence ?? []);
    while (count($slots) < 4) {
        $slots[] = null;
    }
    $slots = array_slice($slots, 0, 4);
    $rows = array_chunk($slots, 2);
@endphp

<div class="classification">
    <span class="en">Public Internal</span> - عام (داخلي)
</div>

<div class="frame">
    <table class="gallery" dir="ltr">
        @foreach ($rows as $row)
            <tr>
                @foreach ($row as $image)
                    <td>
                        @if (! empty($image['path']))
                            <img class="ev-img" src="{{ $image['path'] }}" alt="evidence">
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table>
</div>

<div class="footer-class">
    <span class="en" style="direction:ltr; unicode-bidi:embed;">Public Internal</span> - عام (داخلي)
</div>

</body>
</html>
