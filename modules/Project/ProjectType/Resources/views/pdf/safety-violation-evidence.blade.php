<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: arial, tahoma, dejavusans, sans-serif;
            font-size: 9.5pt;
            color: #000;
            direction: rtl;
        }

        .page {
            border: 1px solid #000;
            padding: 3px;
            width: 100%;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            table-layout: fixed;
        }

        .top td {
            border: none;
            padding: 2px 4px 5px 4px;
            vertical-align: middle;
            height: 16mm;
        }

        .logo {
            width: 62px;
            height: 62px;
        }

        .title {
            text-align: center;
            font-weight: bold;
            font-size: 11pt;
            border-bottom: 1px solid #000 !important;
            padding-bottom: 5px !important;
        }

        /* Fixed 2x2 evidence grid — always 4 cells, same size */
        .gallery td {
            width: 50%;
            border: 1px solid #000;
            padding: 6px;
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
    </style>
</head>
<body>
@php
    $logo = $header['company_logo'] ?? null;
    // Always exactly 4 slots for a fixed page layout.
    $slots = array_values($evidence ?? []);
    while (count($slots) < 4) {
        $slots[] = null;
    }
    $slots = array_slice($slots, 0, 4);
    $rows = array_chunk($slots, 2);
@endphp

<div class="page">
    <table class="top" dir="rtl">
        <tr>
            <td style="width:44px; text-align:center;">
                @if ($logo)
                    <img class="logo" src="{{ $logo }}" alt="logo">
                @endif
            </td>
            <td class="title">Violation Report &nbsp;&nbsp; محضر مخالفة</td>
            <td style="width:44px;"></td>
        </tr>
    </table>

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
</body>
</html>
