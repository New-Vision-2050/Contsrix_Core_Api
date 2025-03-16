<?php

namespace Modules\Country\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Setting\Models\Driver;

class CountrySeederTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        \DB::table('countries')->insertOrIgnore(array(
            0 =>
                array(
                    'id' => 1,
                    'shortname' => 'AF',
                    'name' => 'Afghanistan',
                    'name_ar' => 'أفغانستان',
                    'phonecode' => 93,
                    'status' => 0,
                ),
            1 =>
                array(
                    'id' => 2,
                    'shortname' => 'AL',
                    'name' => 'Albania',
                    'name_ar' => 'ألبانيا',
                    'phonecode' => 355,
                    'status' => 0,
                ),
            2 =>
                array(
                    'id' => 3,
                    'shortname' => 'DZ',
                    'name' => 'Algeria',
                    'name_ar' => 'الجزائر',
                    'phonecode' => 213,
                    'status' => 0,
                ),
            3 =>
                array(
                    'id' => 4,
                    'shortname' => 'AS',
                    'name' => 'American Samoa',
                    'name_ar' => 'ساموا الأمريكية',
                    'phonecode' => 1684,
                    'status' => 0,
                ),
            4 =>
                array(
                    'id' => 5,
                    'shortname' => 'AD',
                    'name' => 'Andorra',
                    'name_ar' => 'أندورا',
                    'phonecode' => 376,
                    'status' => 0,
                ),
            5 =>
                array(
                    'id' => 6,
                    'shortname' => 'AO',
                    'name' => 'Angola',
                    'name_ar' => 'أنغولا',
                    'phonecode' => 244,
                    'status' => 0,
                ),
            6 =>
                array(
                    'id' => 7,
                    'shortname' => 'AI',
                    'name' => 'Anguilla',
                    'name_ar' => 'أنجويلا',
                    'phonecode' => 1264,
                    'status' => 0,
                ),
            7 =>
                array(
                    'id' => 8,
                    'shortname' => 'AQ',
                    'name' => 'Antarctica',
                    'name_ar' => 'أنتاركتيكا',
                    'phonecode' => 0,
                    'status' => 0,
                ),
            8 =>
                array(
                    'id' => 9,
                    'shortname' => 'AG',
                    'name' => 'Antigua And Barbuda',
                    'name_ar' => 'أنتيغوا وبربودا',
                    'phonecode' => 1268,
                    'status' => 0,
                ),
            9 =>
                array(
                    'id' => 10,
                    'shortname' => 'AR',
                    'name' => 'Argentina',
                    'name_ar' => 'الأرجنتين',
                    'phonecode' => 54,
                    'status' => 0,
                ),
            10 =>
                array(
                    'id' => 11,
                    'shortname' => 'AM',
                    'name' => 'Armenia',
                    'name_ar' => 'أرمينيا',
                    'phonecode' => 374,
                    'status' => 0,
                ),
            11 =>
                array(
                    'id' => 12,
                    'shortname' => 'AW',
                    'name' => 'Aruba',
                    'name_ar' => 'أروبا',
                    'phonecode' => 297,
                    'status' => 0,
                ),
            12 =>
                array(
                    'id' => 13,
                    'shortname' => 'AU',
                    'name' => 'Australia',
                    'name_ar' => 'أستراليا',
                    'phonecode' => 61,
                    'status' => 0,
                ),
            13 =>
                array(
                    'id' => 14,
                    'shortname' => 'AT',
                    'name' => 'Austria',
                    'name_ar' => 'النمسا',
                    'phonecode' => 43,
                    'status' => 0,
                ),
            14 =>
                array(
                    'id' => 15,
                    'shortname' => 'AZ',
                    'name' => 'Azerbaijan',
                    'name_ar' => 'أذربيجان',
                    'phonecode' => 994,
                    'status' => 0,
                ),
            15 =>
                array(
                    'id' => 16,
                    'shortname' => 'BS',
                    'name' => 'Bahamas The',
                    'name_ar' => 'الباهاما',
                    'phonecode' => 1242,
                    'status' => 0,
                ),
            16 =>
                array(
                    'id' => 17,
                    'shortname' => 'BH',
                    'name' => 'Bahrain',
                    'name_ar' => 'البحرين',
                    'phonecode' => 973,
                    'status' => 0,
                ),
            17 =>
                array(
                    'id' => 18,
                    'shortname' => 'BD',
                    'name' => 'Bangladesh',
                    'name_ar' => 'بنغلاديش',
                    'phonecode' => 880,
                    'status' => 0,
                ),
            18 =>
                array(
                    'id' => 19,
                    'shortname' => 'BB',
                    'name' => 'Barbados',
                    'name_ar' => 'بربادوس',
                    'phonecode' => 1246,
                    'status' => 0,
                ),
            19 =>
                array(
                    'id' => 20,
                    'shortname' => 'BY',
                    'name' => 'Belarus',
                    'name_ar' => 'بيلاروسيا',
                    'phonecode' => 375,
                    'status' => 0,
                ),
            20 =>
                array(
                    'id' => 21,
                    'shortname' => 'BE',
                    'name' => 'Belgium',
                    'name_ar' => 'بلجيكا',
                    'phonecode' => 32,
                    'status' => 0,
                ),
            21 =>
                array(
                    'id' => 22,
                    'shortname' => 'BZ',
                    'name' => 'Belize',
                    'name_ar' => 'بليز',
                    'phonecode' => 501,
                    'status' => 0,
                ),
            22 =>
                array(
                    'id' => 23,
                    'shortname' => 'BJ',
                    'name' => 'Benin',
                    'name_ar' => 'بنين',
                    'phonecode' => 229,
                    'status' => 0,
                ),
            23 =>
                array(
                    'id' => 24,
                    'shortname' => 'BM',
                    'name' => 'Bermuda',
                    'name_ar' => 'برمودا',
                    'phonecode' => 1441,
                    'status' => 0,
                ),
            24 =>
                array(
                    'id' => 25,
                    'shortname' => 'BT',
                    'name' => 'Bhutan',
                    'name_ar' => 'بوتان',
                    'phonecode' => 975,
                    'status' => 0,
                ),
            25 =>
                array(
                    'id' => 26,
                    'shortname' => 'BO',
                    'name' => 'Bolivia',
                    'name_ar' => 'بوليفيا',
                    'phonecode' => 591,
                    'status' => 0,
                ),
            26 =>
                array(
                    'id' => 27,
                    'shortname' => 'BA',
                    'name' => 'Bosnia and Herzegovina',
                    'name_ar' => 'البوسنة والهرسك',
                    'phonecode' => 387,
                    'status' => 0,
                ),
            27 =>
                array(
                    'id' => 28,
                    'shortname' => 'BW',
                    'name' => 'Botswana',
                    'name_ar' => 'بوتسوانا',
                    'phonecode' => 267,
                    'status' => 0,
                ),
            28 =>
                array(
                    'id' => 29,
                    'shortname' => 'BV',
                    'name' => 'Bouvet Island',
                    'name_ar' => 'جزيرة بوفيه',
                    'phonecode' => 0,
                    'status' => 0,
                ),
            29 =>
                array(
                    'id' => 30,
                    'shortname' => 'BR',
                    'name' => 'Brazil',
                    'name_ar' => 'البرازيل',
                    'phonecode' => 55,
                    'status' => 0,
                ),
            30 =>
                array(
                    'id' => 31,
                    'shortname' => 'IO',
                    'name' => 'British Indian Ocean Territory',
                    'name_ar' => 'إقليم المحيط الهندي البريطاني',
                    'phonecode' => 246,
                    'status' => 0,
                ),
            31 =>
                array(
                    'id' => 32,
                    'shortname' => 'BN',
                    'name' => 'Brunei',
                    'name_ar' => 'بروناي',
                    'phonecode' => 673,
                    'status' => 0,
                ),
            32 =>
                array(
                    'id' => 33,
                    'shortname' => 'BG',
                    'name' => 'Bulgaria',
                    'name_ar' => 'بلغاريا',
                    'phonecode' => 359,
                    'status' => 0,
                ),
            33 =>
                array(
                    'id' => 34,
                    'shortname' => 'BF',
                    'name' => 'Burkina Faso',
                    'name_ar' => 'بوركينا فاسو',
                    'phonecode' => 226,
                    'status' => 0,
                ),
            34 =>
                array(
                    'id' => 35,
                    'shortname' => 'BI',
                    'name' => 'Burundi',
                    'name_ar' => 'بوروندي',
                    'phonecode' => 257,
                    'status' => 0,
                ),
            35 =>
                array(
                    'id' => 36,
                    'shortname' => 'KH',
                    'name' => 'Cambodia',
                    'name_ar' => 'كمبوديا',
                    'phonecode' => 855,
                    'status' => 0,
                ),
            36 =>
                array(
                    'id' => 37,
                    'shortname' => 'CM',
                    'name' => 'Cameroon',
                    'name_ar' => 'الكاميرون',
                    'phonecode' => 237,
                    'status' => 0,
                ),
            37 =>
                array(
                    'id' => 38,
                    'shortname' => 'CA',
                    'name' => 'Canada',
                    'name_ar' => 'كندا',
                    'phonecode' => 1,
                    'status' => 0,
                ),
            38 =>
                array(
                    'id' => 39,
                    'shortname' => 'CV',
                    'name' => 'Cape Verde',
                    'name_ar' => 'الرأس الأخضر',
                    'phonecode' => 238,
                    'status' => 0,
                ),
            39 =>
                array(
                    'id' => 40,
                    'shortname' => 'KY',
                    'name' => 'Cayman Islands',
                    'name_ar' => 'جزر كايمان',
                    'phonecode' => 1345,
                    'status' => 0,
                ),
            40 =>
                array(
                    'id' => 41,
                    'shortname' => 'CF ',
                    'name' => 'Central African Republic',
                    'name_ar' => 'جمهورية أفريقيا الوسطى',
                    'phonecode' => 236,
                    'status' => 0,
                ),
            41 =>
                array(
                    'id' => 42,
                    'shortname' => 'TD',
                    'name' => 'Chad',
                    'name_ar' => 'تشاد',
                    'phonecode' => 235,
                    'status' => 0,
                ),
            42 =>
                array(
                    'id' => 43,
                    'shortname' => 'CL',
                    'name' => 'Chile',
                    'name_ar' => 'شيلي',
                    'phonecode' => 56,
                    'status' => 0,
                ),
            43 =>
                array(
                    'id' => 44,
                    'shortname' => 'CN',
                    'name' => 'China',
                    'name_ar' => 'الصين',
                    'phonecode' => 86,
                    'status' => 0,
                ),
            44 =>
                array(
                    'id' => 45,
                    'shortname' => 'CX',
                    'name' => 'Christmas Island',
                    'name_ar' => 'جزيرة عيد الميلاد',
                    'phonecode' => 61,
                    'status' => 0,
                ),
            45 =>
                array(
                    'id' => 46,
                    'shortname' => 'CC',
                    'name' => 'Cocos (Keeling) Islands',
                    'name_ar' => 'جزر كوكوس (كيلينغ)',
                    'phonecode' => 672,
                    'status' => 0,
                ),
            46 =>
                array(
                    'id' => 47,
                    'shortname' => 'CO',
                    'name' => 'Colombia',
                    'name_ar' => 'كولومبيا',
                    'phonecode' => 57,
                    'status' => 0,
                ),
            47 =>
                array(
                    'id' => 48,
                    'shortname' => 'KM',
                    'name' => 'Comoros',
                    'name_ar' => 'جزر القمر',
                    'phonecode' => 269,
                    'status' => 0,
                ),
            48 =>
                array(
                    'id' => 49,
                    'shortname' => 'CG',
                    'name' => 'Republic Of The Congo',
                    'name_ar' => 'جمهورية الكونغو',
                    'phonecode' => 242,
                    'status' => 0,
                ),
            49 =>
                array(
                    'id' => 50,
                    'shortname' => 'CD',
                    'name' => 'Democratic Republic Of The Congo',
                    'name_ar' => 'جمهورية الكونغو الديمقراطية',
                    'phonecode' => 242,
                    'status' => 0,
                ),
            50 =>
                array(
                    'id' => 51,
                    'shortname' => 'CK',
                    'name' => 'Cook Islands',
                    'name_ar' => 'جزر كوك',
                    'phonecode' => 682,
                    'status' => 0,
                ),
            51 =>
                array(
                    'id' => 52,
                    'shortname' => 'CR',
                    'name' => 'Costa Rica',
                    'name_ar' => 'كوستاريكا',
                    'phonecode' => 506,
                    'status' => 0,
                ),
            52 =>
                array(
                    'id' => 53,
                    'shortname' => 'CI',
                    'name' => 'Cote D\'Ivoire (Ivory Coast)',
                    'name_ar' => 'ساحل العاج',
                    'phonecode' => 225,
                    'status' => 0,
                ),
            53 =>
                array(
                    'id' => 54,
                    'shortname' => 'HR',
                    'name' => 'Croatia (Hrvatska)',
                    'name_ar' => 'كرواتيا',
                    'phonecode' => 385,
                    'status' => 0,
                ),
            54 =>
                array(
                    'id' => 55,
                    'shortname' => 'CU',
                    'name' => 'Cuba',
                    'name_ar' => 'كوبا',
                    'phonecode' => 53,
                    'status' => 0,
                ),
            55 =>
                array(
                    'id' => 56,
                    'shortname' => 'CY',
                    'name' => 'Cyprus',
                    'name_ar' => 'قبرص',
                    'phonecode' => 357,
                    'status' => 0,
                ),
            56 =>
                array(
                    'id' => 57,
                    'shortname' => 'CZ',
                    'name' => 'Czech Republic',
                    'name_ar' => 'جمهورية التشيك',
                    'phonecode' => 420,
                    'status' => 0,
                ),
            57 =>
                array(
                    'id' => 58,
                    'shortname' => 'DK',
                    'name' => 'Denmark',
                    'name_ar' => 'الدنمارك',
                    'phonecode' => 45,
                    'status' => 0,
                ),
            58 =>
                array(
                    'id' => 59,
                    'shortname' => 'DJ',
                    'name' => 'Djibouti',
                    'name_ar' => 'جيبوتي',
                    'phonecode' => 253,
                    'status' => 0,
                ),
            59 =>
                array(
                    'id' => 60,
                    'shortname' => 'DM',
                    'name' => 'Dominica',
                    'name_ar' => 'دومينيكا',
                    'phonecode' => 1767,
                    'status' => 0,
                ),
            60 =>
                array(
                    'id' => 61,
                    'shortname' => 'DO',
                    'name' => 'Dominican Republic',
                    'name_ar' => 'جمهورية الدومينيكان',
                    'phonecode' => 1809,
                    'status' => 0,
                ),
            61 =>
                array(
                    'id' => 62,
                    'shortname' => 'TP',
                    'name' => 'East Timor',
                    'name_ar' => 'تيمور الشرقية',
                    'phonecode' => 670,
                    'status' => 0,
                ),
            62 =>
                array(
                    'id' => 63,
                    'shortname' => 'EC',
                    'name' => 'Ecuador',
                    'name_ar' => 'الإكوادور',
                    'phonecode' => 593,
                    'status' => 0,
                ),
            63 =>
                array(
                    'id' => 64,
                    'shortname' => 'EG',
                    'name' => 'Egypt',
                    'name_ar' => 'مصر',
                    'phonecode' => 20,
                    'status' => 1,
                ),
            64 =>
                array(
                    'id' => 65,
                    'shortname' => 'SV',
                    'name' => 'El Salvador',
                    'name_ar' => 'السلفادور',
                    'phonecode' => 503,
                    'status' => 1,
                ),
            65 =>
                array(
                    'id' => 66,
                    'shortname' => 'GQ',
                    'name' => 'Equatorial Guinea',
                    'name_ar' => 'غينيا الاستوائية',
                    'phonecode' => 240,
                    'status' => 0,
                ),
            66 =>
                array(
                    'id' => 67,
                    'shortname' => 'ER',
                    'name' => 'Eritrea',
                    'name_ar' => 'إريتريا',
                    'phonecode' => 291,
                    'status' => 0,
                ),
            67 =>
                array(
                    'id' => 68,
                    'shortname' => 'EE',
                    'name' => 'Estonia',
                    'name_ar' => 'إستونيا',
                    'phonecode' => 372,
                    'status' => 0,
                ),
            68 =>
                array(
                    'id' => 69,
                    'shortname' => 'ET',
                    'name' => 'Ethiopia',
                    'name_ar' => 'إثيوبيا',
                    'phonecode' => 251,
                    'status' => 0,
                ),
            69 =>
                array(
                    'id' => 70,
                    'shortname' => 'XA',
                    'name' => 'External Territories of Australia',
                    'name_ar' => 'الأراضي الخارجية لأستراليا',
                    'phonecode' => 61,
                    'status' => 0,
                ),
            70 =>
                array(
                    'id' => 71,
                    'shortname' => 'FK',
                    'name' => 'Falkland Islands',
                    'name_ar' => 'جزر فوكلاند',
                    'phonecode' => 500,
                    'status' => 0,
                ),
            71 =>
                array(
                    'id' => 72,
                    'shortname' => 'FO',
                    'name' => 'Faroe Islands',
                    'name_ar' => 'جزر فارو',
                    'phonecode' => 298,
                    'status' => 0,
                ),
            72 =>
                array(
                    'id' => 73,
                    'shortname' => 'FJ',
                    'name' => 'Fiji Islands',
                    'name_ar' => 'جزر فيجي',
                    'phonecode' => 679,
                    'status' => 0,
                ),
            73 =>
                array(
                    'id' => 74,
                    'shortname' => 'FI',
                    'name' => 'Finland',
                    'name_ar' => 'فنلندا',
                    'phonecode' => 358,
                    'status' => 0,
                ),
            74 =>
                array(
                    'id' => 75,
                    'shortname' => 'FR',
                    'name' => 'France',
                    'name_ar' => 'فرنسا',
                    'phonecode' => 33,
                    'status' => 0,
                ),
            75 =>
                array(
                    'id' => 76,
                    'shortname' => 'GF',
                    'name' => 'French Guiana',
                    'name_ar' => 'غويانا الفرنسية',
                    'phonecode' => 594,
                    'status' => 0,
                ),
            76 =>
                array(
                    'id' => 77,
                    'shortname' => 'PF',
                    'name' => 'French Polynesia',
                    'name_ar' => 'بولينيزيا الفرنسية',
                    'phonecode' => 689,
                    'status' => 0,
                ),
            77 =>
                array(
                    'id' => 78,
                    'shortname' => 'TF',
                    'name' => 'French Southern Territories',
                    'name_ar' => 'الأراضي الجنوبية الفرنسية',
                    'phonecode' => 0,
                    'status' => 0,
                ),
            78 =>
                array(
                    'id' => 79,
                    'shortname' => 'GA',
                    'name' => 'Gabon',
                    'name_ar' => 'الغابون',
                    'phonecode' => 241,
                    'status' => 0,
                ),
            79 =>
                array(
                    'id' => 80,
                    'shortname' => 'GM',
                    'name' => 'Gambia The',
                    'name_ar' => 'غامبيا',
                    'phonecode' => 220,
                    'status' => 0,
                ),
            80 =>
                array(
                    'id' => 81,
                    'shortname' => 'GE',
                    'name' => 'Georgia',
                    'name_ar' => 'جورجيا',
                    'phonecode' => 995,
                    'status' => 0,
                ),
            81 =>
                array(
                    'id' => 82,
                    'shortname' => 'DE',
                    'name' => 'Germany',
                    'name_ar' => 'ألمانيا',
                    'phonecode' => 49,
                    'status' => 0,
                ),
            82 =>
                array(
                    'id' => 83,
                    'shortname' => 'GH',
                    'name' => 'Ghana',
                    'name_ar' => 'غانا',
                    'phonecode' => 233,
                    'status' => 0,
                ),
            83 =>
                array(
                    'id' => 84,
                    'shortname' => 'GI',
                    'name' => 'Gibraltar',
                    'name_ar' => 'جبل طارق',
                    'phonecode' => 350,
                    'status' => 0,
                ),
            84 =>
                array(
                    'id' => 85,
                    'shortname' => 'GR',
                    'name' => 'Greece',
                    'name_ar' => 'اليونان',
                    'phonecode' => 30,
                    'status' => 0,
                ),
            85 =>
                array(
                    'id' => 86,
                    'shortname' => 'GL',
                    'name' => 'Greenland',
                    'name_ar' => 'جرينلاند',
                    'phonecode' => 299,
                    'status' => 0,
                ),
            86 =>
                array(
                    'id' => 87,
                    'shortname' => 'GD',
                    'name' => 'Grenada',
                    'name_ar' => 'غرينادا',
                    'phonecode' => 1473,
                    'status' => 0,
                ),
            87 =>
                array(
                    'id' => 88,
                    'shortname' => 'GP',
                    'name' => 'Guadeloupe',
                    'name_ar' => 'جوادلوب',
                    'phonecode' => 590,
                    'status' => 0,
                ),
            88 =>
                array(
                    'id' => 89,
                    'shortname' => 'GU',
                    'name' => 'Guam',
                    'name_ar' => 'جوام',
                    'phonecode' => 1671,
                    'status' => 0,
                ),
            89 =>
                array(
                    'id' => 90,
                    'shortname' => 'GT',
                    'name' => 'Guatemala',
                    'name_ar' => 'غواتيمالا',
                    'phonecode' => 502,
                    'status' => 0,
                ),
            90 =>
                array(
                    'id' => 91,
                    'shortname' => 'XU',
                    'name' => 'Guernsey and Alderney',
                    'name_ar' => 'غيرنزي وألدرني',
                    'phonecode' => 44,
                    'status' => 0,
                ),
            91 =>
                array(
                    'id' => 92,
                    'shortname' => 'GN',
                    'name' => 'Guinea',
                    'name_ar' => 'غينيا',
                    'phonecode' => 224,
                    'status' => 0,
                ),
            92 =>
                array(
                    'id' => 93,
                    'shortname' => 'GW',
                    'name' => 'Guinea-Bissau',
                    'name_ar' => 'غينيا بيساو',
                    'phonecode' => 245,
                    'status' => 0,
                ),
            93 =>
                array(
                    'id' => 94,
                    'shortname' => 'GY',
                    'name' => 'Guyana',
                    'name_ar' => 'غيانا',
                    'phonecode' => 592,
                    'status' => 0,
                ),
            94 =>
                array(
                    'id' => 95,
                    'shortname' => 'HT',
                    'name' => 'Haiti',
                    'name_ar' => 'هايتي',
                    'phonecode' => 509,
                    'status' => 0,
                ),
            95 =>
                array(
                    'id' => 96,
                    'shortname' => 'HM',
                    'name' => 'Heard and McDonald Islands',
                    'name_ar' => 'جزر هيرد وماكدونالد',
                    'phonecode' => 0,
                    'status' => 0,
                ),
            96 =>
                array(
                    'id' => 97,
                    'shortname' => 'HN',
                    'name' => 'Honduras',
                    'name_ar' => 'هندوراس',
                    'phonecode' => 504,
                    'status' => 0,
                ),
            97 =>
                array(
                    'id' => 98,
                    'shortname' => 'HK',
                    'name' => 'Hong Kong S.A.R.',
                    'name_ar' => 'هونغ كونغ',
                    'phonecode' => 852,
                    'status' => 0,
                ),
            98 =>
                array(
                    'id' => 99,
                    'shortname' => 'HU',
                    'name' => 'Hungary',
                    'name_ar' => 'المجر',
                    'phonecode' => 36,
                    'status' => 0,
                ),
            99 =>
                array(
                    'id' => 100,
                    'shortname' => 'IS',
                    'name' => 'Iceland',
                    'name_ar' => 'آيسلندا',
                    'phonecode' => 354,
                    'status' => 0,
                ),
            100 =>
                array(
                    'id' => 101,
                    'shortname' => 'IN',
                    'name' => 'India',
                    'name_ar' => 'الهند',
                    'phonecode' => 91,
                    'status' => 0,
                ),
            101 =>
                array(
                    'id' => 102,
                    'shortname' => 'ID',
                    'name' => 'Indonesia',
                    'name_ar' => 'إندونيسيا',
                    'phonecode' => 62,
                    'status' => 0,
                ),
            102 =>
                array(
                    'id' => 103,
                    'shortname' => 'IR',
                    'name' => 'Iran',
                    'name_ar' => 'إيران',
                    'phonecode' => 98,
                    'status' => 0,
                ),
            103 =>
                array(
                    'id' => 104,
                    'shortname' => 'IQ',
                    'name' => 'Iraq',
                    'name_ar' => 'العراق',
                    'phonecode' => 964,
                    'status' => 0,
                ),
            104 =>
                array(
                    'id' => 105,
                    'shortname' => 'IE',
                    'name' => 'Ireland',
                    'name_ar' => 'أيرلندا',
                    'phonecode' => 353,
                    'status' => 0,
                ),
            105 =>
                array(
                    'id' => 106,
                    'shortname' => 'IL',
                    'name' => 'Israel',
                    'name_ar' => 'إسرائيل',
                    'phonecode' => 972,
                    'status' => 0,
                ),
            106 =>
                array(
                    'id' => 107,
                    'shortname' => 'IT',
                    'name' => 'Italy',
                    'name_ar' => 'إيطاليا',
                    'phonecode' => 39,
                    'status' => 0,
                ),
            107 =>
                array(
                    'id' => 108,
                    'shortname' => 'JM',
                    'name' => 'Jamaica',
                    'name_ar' => 'جامايكا',
                    'phonecode' => 1876,
                    'status' => 0,
                ),
            108 =>
                array(
                    'id' => 109,
                    'shortname' => 'JP',
                    'name' => 'Japan',
                    'name_ar' => 'اليابان',
                    'phonecode' => 81,
                    'status' => 0,
                ),
            109 =>
                array(
                    'id' => 110,
                    'shortname' => 'XJ',
                    'name' => 'Jersey',
                    'name_ar' => 'جيرسي',
                    'phonecode' => 44,
                    'status' => 0,
                ),
            110 =>
                array(
                    'id' => 111,
                    'shortname' => 'JO',
                    'name' => 'Jordan',
                    'name_ar' => 'الأردن',
                    'phonecode' => 962,
                    'status' => 0,
                ),
            111 =>
                array(
                    'id' => 112,
                    'shortname' => 'KZ',
                    'name' => 'Kazakhstan',
                    'name_ar' => 'كازاخستان',
                    'phonecode' => 7,
                    'status' => 0,
                ),
            112 =>
                array(
                    'id' => 113,
                    'shortname' => 'KE',
                    'name' => 'Kenya',
                    'name_ar' => 'كينيا',
                    'phonecode' => 254,
                    'status' => 0,
                ),
            113 =>
                array(
                    'id' => 114,
                    'shortname' => 'KI',
                    'name' => 'Kiribati',
                    'name_ar' => 'كيريباتي',
                    'phonecode' => 686,
                    'status' => 0,
                ),
            114 =>
                array(
                    'id' => 115,
                    'shortname' => 'KP',
                    'name' => 'Korea North',
                    'name_ar' => 'كوريا الشمالية',
                    'phonecode' => 850,
                    'status' => 0,
                ),
            115 =>
                array(
                    'id' => 116,
                    'shortname' => 'KR',
                    'name' => 'Korea South',
                    'name_ar' => 'كوريا الجنوبية',
                    'phonecode' => 82,
                    'status' => 0,
                ),
            116 =>
                array(
                    'id' => 117,
                    'shortname' => 'KW',
                    'name' => 'Kuwait',
                    'name_ar' => 'الكويت',
                    'phonecode' => 965,
                    'status' => 0,
                ),
            117 =>
                array(
                    'id' => 118,
                    'shortname' => 'KG',
                    'name' => 'Kyrgyzstan',
                    'name_ar' => 'قيرغيزستان',
                    'phonecode' => 996,
                    'status' => 0,
                ),
            118 =>
                array(
                    'id' => 119,
                    'shortname' => 'LA',
                    'name' => 'Laos',
                    'name_ar' => 'لاوس',
                    'phonecode' => 856,
                    'status' => 0,
                ),
            119 =>
                array(
                    'id' => 120,
                    'shortname' => 'LV',
                    'name' => 'Latvia',
                    'name_ar' => 'لاتفيا',
                    'phonecode' => 371,
                    'status' => 0,
                ),
            120 =>
                array(
                    'id' => 121,
                    'shortname' => 'LB',
                    'name' => 'Lebanon',
                    'name_ar' => 'لبنان',
                    'phonecode' => 961,
                    'status' => 0,
                ),
            121 =>
                array(
                    'id' => 122,
                    'shortname' => 'LS',
                    'name' => 'Lesotho',
                    'name_ar' => 'ليسوتو',
                    'phonecode' => 266,
                    'status' => 0,
                ),
            122 =>
                array(
                    'id' => 123,
                    'shortname' => 'LR',
                    'name' => 'Liberia',
                    'name_ar' => 'ليبيريا',
                    'phonecode' => 231,
                    'status' => 0,
                ),
            123 =>
                array(
                    'id' => 124,
                    'shortname' => 'LY',
                    'name' => 'Libya',
                    'name_ar' => 'ليبيا',
                    'phonecode' => 218,
                    'status' => 0,
                ),
            124 =>
                array(
                    'id' => 125,
                    'shortname' => 'LI',
                    'name' => 'Liechtenstein',
                    'name_ar' => 'ليختنشتاين',
                    'phonecode' => 423,
                    'status' => 0,
                ),
            125 =>
                array(
                    'id' => 126,
                    'shortname' => 'LT',
                    'name' => 'Lithuania',
                    'name_ar' => 'ليتوانيا',
                    'phonecode' => 370,
                    'status' => 0,
                ),
            126 =>
                array(
                    'id' => 127,
                    'shortname' => 'LU',
                    'name' => 'Luxembourg',
                    'name_ar' => 'لوكسمبورغ',
                    'phonecode' => 352,
                    'status' => 0,
                ),
            127 =>
                array(
                    'id' => 128,
                    'shortname' => 'MO',
                    'name' => 'Macau S.A.R.',
                    'name_ar' => 'ماكاو',
                    'phonecode' => 853,
                    'status' => 0,
                ),
            128 =>
                array(
                    'id' => 129,
                    'shortname' => 'MK',
                    'name' => 'Macedonia',
                    'name_ar' => 'مقدونيا',
                    'phonecode' => 389,
                    'status' => 0,
                ),
            129 =>
                array(
                    'id' => 130,
                    'shortname' => 'MG',
                    'name' => 'Madagascar',
                    'name_ar' => 'مدغشقر',
                    'phonecode' => 261,
                    'status' => 0,
                ),
            130 =>
                array(
                    'id' => 131,
                    'shortname' => 'MW',
                    'name' => 'Malawi',
                    'name_ar' => 'مالاوي',
                    'phonecode' => 265,
                    'status' => 0,
                ),
            131 =>
                array(
                    'id' => 132,
                    'shortname' => 'MY',
                    'name' => 'Malaysia',
                    'name_ar' => 'ماليزيا',
                    'phonecode' => 60,
                    'status' => 0,
                ),
            132 =>
                array(
                    'id' => 133,
                    'shortname' => 'MV',
                    'name' => 'Maldives',
                    'name_ar' => 'جزر المالديف',
                    'phonecode' => 960,
                    'status' => 0,
                ),
            133 =>
                array(
                    'id' => 134,
                    'shortname' => 'ML',
                    'name' => 'Mali',
                    'name_ar' => 'مالي',
                    'phonecode' => 223,
                    'status' => 0,
                ),
            134 =>
                array(
                    'id' => 135,
                    'shortname' => 'MT',
                    'name' => 'Malta',
                    'name_ar' => 'مالطا',
                    'phonecode' => 356,
                    'status' => 0,
                ),
            135 =>
                array(
                    'id' => 136,
                    'shortname' => 'XM',
                    'name' => 'Man (Isle of)',
                    'name_ar' => 'جزيرة مان',
                    'phonecode' => 44,
                    'status' => 0,
                ),
            136 =>
                array(
                    'id' => 137,
                    'shortname' => 'MH',
                    'name' => 'Marshall Islands',
                    'name_ar' => 'جزر مارشال',
                    'phonecode' => 692,
                    'status' => 0,
                ),
            137 =>
                array(
                    'id' => 138,
                    'shortname' => 'MQ',
                    'name' => 'Martinique',
                    'name_ar' => 'مارتينيك',
                    'phonecode' => 596,
                    'status' => 0,
                ),
            138 =>
                array(
                    'id' => 139,
                    'shortname' => 'MR',
                    'name' => 'Mauritania',
                    'name_ar' => 'موريتانيا',
                    'phonecode' => 222,
                    'status' => 0,
                ),
            139 =>
                array(
                    'id' => 140,
                    'shortname' => 'MU',
                    'name' => 'Mauritius',
                    'name_ar' => 'موريشيوس',
                    'phonecode' => 230,
                    'status' => 0,
                ),
            140 =>
                array(
                    'id' => 141,
                    'shortname' => 'YT',
                    'name' => 'Mayotte',
                    'name_ar' => 'مايوت',
                    'phonecode' => 269,
                    'status' => 0,
                ),
            141 =>
                array(
                    'id' => 142,
                    'shortname' => 'MX',
                    'name' => 'Mexico',
                    'name_ar' => 'المكسيك',
                    'phonecode' => 52,
                    'status' => 0,
                ),
            142 =>
                array(
                    'id' => 143,
                    'shortname' => 'FM',
                    'name' => 'Micronesia',
                    'name_ar' => 'ميك رونيسيا',
                    'phonecode' => 691,
                    'status' => 0,
                ),
            143 =>
                array(
                    'id' => 144,
                    'shortname' => 'MD',
                    'name' => 'Moldova',
                    'name_ar' => 'مولدوفا',
                    'phonecode' => 373,
                    'status' => 0,
                ),
            144 =>
                array(
                    'id' => 145,
                    'shortname' => 'MC',
                    'name' => 'Monaco',
                    'name_ar' => 'موناكو',
                    'phonecode' => 377,
                    'status' => 0,
                ),
            145 =>
                array(
                    'id' => 146,
                    'shortname' => 'MN',
                    'name' => 'Mongolia',
                    'name_ar' => 'منغوليا',
                    'phonecode' => 976,
                    'status' => 0,
                ),
            146 =>
                array(
                    'id' => 147,
                    'shortname' => 'MS',
                    'name' => 'Montserrat',
                    'name_ar' => 'مونتسرات',
                    'phonecode' => 1664,
                    'status' => 0,
                ),
            147 =>
                array(
                    'id' => 148,
                    'shortname' => 'MA',
                    'name' => 'Morocco',
                    'name_ar' => 'المغرب',
                    'phonecode' => 212,
                    'status' => 0,
                ),
            148 =>
                array(
                    'id' => 149,
                    'shortname' => 'MZ',
                    'name' => 'Mozambique',
                    'name_ar' => 'موزامبيق',
                    'phonecode' => 258,
                    'status' => 0,
                ),
            149 =>
                array(
                    'id' => 150,
                    'shortname' => 'MM',
                    'name' => 'Myanmar',
                    'name_ar' => 'ميانمار',
                    'phonecode' => 95,
                    'status' => 0,
                ),
            150 =>
                array(
                    'id' => 151,
                    'shortname' => 'NA',
                    'name' => 'Namibia',
                    'name_ar' => 'ناميبيا',
                    'phonecode' => 264,
                    'status' => 0,
                ),
            151 =>
                array(
                    'id' => 152,
                    'shortname' => 'NR',
                    'name' => 'Nauru',
                    'name_ar' => 'ناورو',
                    'phonecode' => 674,
                    'status' => 0,
                ),
            152 =>
                array(
                    'id' => 153,
                    'shortname' => 'NP',
                    'name' => 'Nepal',
                    'name_ar' => 'نيبال',
                    'phonecode' => 977,
                    'status' => 0,
                ),
            153 =>
                array(
                    'id' => 154,
                    'shortname' => 'AN',
                    'name' => 'Netherlands Antilles',
                    'name_ar' => 'جزر الأنتيل الهولندية',
                    'phonecode' => 599,
                    'status' => 0,
                ),
            154 =>
                array(
                    'id' => 155,
                    'shortname' => 'NL',
                    'name' => 'Netherlands The',
                    'name_ar' => 'هولندا',
                    'phonecode' => 31,
                    'status' => 0,
                ),
            155 =>
                array(
                    'id' => 156,
                    'shortname' => 'NC',
                    'name' => 'New Caledonia',
                    'name_ar' => 'كاليدونيا الجديدة',
                    'phonecode' => 687,
                    'status' => 0,
                ),
            156 =>
                array(
                    'id' => 157,
                    'shortname' => 'NZ',
                    'name' => 'New Zealand',
                    'name_ar' => 'نيوزيلندا',
                    'phonecode' => 64,
                    'status' => 0,
                ),
            157 =>
                array(
                    'id' => 158,
                    'shortname' => 'NI',
                    'name' => 'Nicaragua',
                    'name_ar' => 'نيكاراغوا',
                    'phonecode' => 505,
                    'status' => 0,
                ),
            158 =>
                array(
                    'id' => 159,
                    'shortname' => 'NE',
                    'name' => 'Niger',
                    'name_ar' => 'النيجر',
                    'phonecode' => 227,
                    'status' => 0,
                ),
            159 =>
                array(
                    'id' => 160,
                    'shortname' => 'NG',
                    'name' => 'Nigeria',
                    'name_ar' => 'نيجيريا',
                    'phonecode' => 234,
                    'status' => 0,
                ),
            160 =>
                array(
                    'id' => 161,
                    'shortname' => 'NU',
                    'name' => 'Niue',
                    'name_ar' => 'نيوي',
                    'phonecode' => 683,
                    'status' => 0,
                ),
            161 =>
                array(
                    'id' => 162,
                    'shortname' => 'NF',
                    'name' => 'Norfolk Island',
                    'name_ar' => 'جزيرة نورفولك',
                    'phonecode' => 672,
                    'status' => 0,
                ),
            162 =>
                array(
                    'id' => 163,
                    'shortname' => 'MP',
                    'name' => 'Northern Mariana Islands',
                    'name_ar' => 'جزر ماريانا الشمالية',
                    'phonecode' => 1670,
                    'status' => 0,
                ),
            163 =>
                array(
                    'id' => 164,
                    'shortname' => 'NO',
                    'name' => 'Norway',
                    'name_ar' => 'النرويج',
                    'phonecode' => 47,
                    'status' => 0,
                ),
            164 =>
                array(
                    'id' => 165,
                    'shortname' => 'OM',
                    'name' => 'Oman',
                    'name_ar' => 'عمان',
                    'phonecode' => 968,
                    'status' => 0,
                ),
            165 =>
                array(
                    'id' => 166,
                    'shortname' => 'PK',
                    'name' => 'Pakistan',
                    'name_ar' => 'باكستان',
                    'phonecode' => 92,
                    'status' => 0,
                ),
            166 =>
                array(
                    'id' => 167,
                    'shortname' => 'PW',
                    'name' => 'Palau',
                    'name_ar' => 'بالاو',
                    'phonecode' => 680,
                    'status' => 0,
                ),
            167 =>
                array(
                    'id' => 168,
                    'shortname' => 'PS',
                    'name' => 'Palestinian Territory Occupied',
                    'name_ar' => 'الأراضي الفلسطينية',
                    'phonecode' => 970,
                    'status' => 0,
                ),
            168 =>
                array(
                    'id' => 169,
                    'shortname' => 'PA',
                    'name' => 'Panama',
                    'name_ar' => 'بنما',
                    'phonecode' => 507,
                    'status' => 0,
                ),
            169 =>
                array(
                    'id' => 170,
                    'shortname' => 'PG',
                    'name' => 'Papua new Guinea',
                    'name_ar' => 'بابوا غينيا الجديدة',
                    'phonecode' => 675,
                    'status' => 0,
                ),
            170 =>
                array(
                    'id' => 171,
                    'shortname' => 'PY',
                    'name' => 'Paraguay',
                    'name_ar' => 'باراغواي',
                    'phonecode' => 595,
                    'status' => 0,
                ),
            171 =>
                array(
                    'id' => 172,
                    'shortname' => 'PE',
                    'name' => 'Peru',
                    'name_ar' => 'بيرو',
                    'phonecode' => 51,
                    'status' => 0,
                ),
            172 =>
                array(
                    'id' => 173,
                    'shortname' => 'PH',
                    'name' => 'Philippines',
                    'name_ar' => 'الفلبين',
                    'phonecode' => 63,
                    'status' => 0,
                ),
            173 =>
                array(
                    'id' => 174,
                    'shortname' => 'PN',
                    'name' => 'Pitcairn Island',
                    'name_ar' => 'جزيرة بيتكيرن',
                    'phonecode' => 0,
                    'status' => 0,
                ),
            174 =>
                array(
                    'id' => 175,
                    'shortname' => 'PL',
                    'name' => 'Poland',
                    'name_ar' => 'بولندا',
                    'phonecode' => 48,
                    'status' => 0,
                ),
            175 =>
                array(
                    'id' => 176,
                    'shortname' => 'PT',
                    'name' => 'Portugal',
                    'name_ar' => 'البرتغال',
                    'phonecode' => 351,
                    'status' => 0,
                ),
            176 =>
                array(
                    'id' => 177,
                    'shortname' => 'PR',
                    'name' => 'Puerto Rico',
                    'name_ar' => 'بورتوريكو',
                    'phonecode' => 1787,
                    'status' => 0,
                ),
            177 =>
                array(
                    'id' => 178,
                    'shortname' => 'QA',
                    'name' => 'Qatar',
                    'name_ar' => 'قطر',
                    'phonecode' => 974,
                    'status' => 0,
                ),
            178 =>
                array(
                    'id' => 179,
                    'shortname' => 'RE',
                    'name' => 'Reunion',
                    'name_ar' => 'ريونيون',
                    'phonecode' => 262,
                    'status' => 0,
                ),
            179 =>
                array(
                    'id' => 180,
                    'shortname' => 'RO',
                    'name' => 'Romania',
                    'name_ar' => 'رومانيا',
                    'phonecode' => 40,
                    'status' => 0,
                ),
            180 =>
                array(
                    'id' => 181,
                    'shortname' => 'RU',
                    'name' => 'Russia',
                    'name_ar' => 'روسيا',
                    'phonecode' => 70,
                    'status' => 0,
                ),
            181 =>
                array(
                    'id' => 182,
                    'shortname' => 'RW',
                    'name' => 'Rwanda',
                    'name_ar' => 'رواندا',
                    'phonecode' => 250,
                    'status' => 0,
                ),
            182 =>
                array(
                    'id' => 183,
                    'shortname' => 'SH',
                    'name' => 'Saint Helena',
                    'name_ar' => 'سانت هيلينا',
                    'phonecode' => 290,
                    'status' => 0,
                ),
            183 =>
                array(
                    'id' => 184,
                    'shortname' => 'KN',
                    'name' => 'Saint Kitts And Nevis',
                    'name_ar' => 'سانت كيتس ونيفيس',
                    'phonecode' => 1869,
                    'status' => 0,
                ),
            184 =>
                array(
                    'id' => 185,
                    'shortname' => 'LC',
                    'name' => 'Saint Lucia',
                    'name_ar' => 'سانت لوسيا',
                    'phonecode' => 1758,
                    'status' => 0,
                ),
            185 =>
                array(
                    'id' => 186,
                    'shortname' => 'PM',
                    'name' => 'Saint Pierre and Miquelon',
                    'name_ar' => 'سانت بيير وميكلون',
                    'phonecode' => 508,
                    'status' => 0,
                ),
            186 =>
                array(
                    'id' => 187,
                    'shortname' => 'VC',
                    'name' => 'Saint Vincent And The Grenadines',
                    'name_ar' => 'سانت فنسنت وجزر غرينادين',
                    'phonecode' => 1784,
                    'status' => 0,
                ),
            187 =>
                array(
                    'id' => 188,
                    'shortname' => 'WS',
                    'name' => 'Samoa',
                    'name_ar' => 'ساموا',
                    'phonecode' => 684,
                    'status' => 0,
                ),
            188 =>
                array(
                    'id' => 189,
                    'shortname' => 'SM',
                    'name' => 'San Marino',
                    'name_ar' => 'سان مارينو',
                    'phonecode' => 378,
                    'status' => 0,
                ),
            189 =>
                array(
                    'id' => 190,
                    'shortname' => 'ST',
                    'name' => 'Sao Tome and Principe',
                    'name_ar' => 'ساو تومي وبرينسيبي',
                    'phonecode' => 239,
                    'status' => 0,
                ),
            190 =>
                array(
                    'id' => 191,
                    'shortname' => 'SA',
                    'name' => 'Saudi Arabia',
                    'name_ar' => 'السعودية',
                    'phonecode' => 966,
                    'status' => 1,
                ),
            191 =>
                array(
                    'id' => 192,
                    'shortname' => 'SN',
                    'name' => 'Senegal',
                    'name_ar' => 'السنغال',
                    'phonecode' => 221,
                    'status' => 0,
                ),
            192 =>
                array(
                    'id' => 193,
                    'shortname' => 'RS',
                    'name' => 'Serbia',
                    'name_ar' => 'صربيا',
                    'phonecode' => 381,
                    'status' => 0,
                ),
            193 =>
                array(
                    'id' => 194,
                    'shortname' => 'SC',
                    'name' => 'Seychelles',
                    'name_ar' => 'سيشيل',
                    'phonecode' => 248,
                    'status' => 0,
                ),
            194 =>
                array(
                    'id' => 195,
                    'shortname' => 'SL',
                    'name' => 'Sierra Leone',
                    'name_ar' => 'سيراليون',
                    'phonecode' => 232,
                    'status' => 0,
                ),
            195 =>
                array(
                    'id' => 196,
                    'shortname' => 'SG',
                    'name' => 'Singapore',
                    'name_ar' => 'سنغافورة',
                    'phonecode' => 65,
                    'status' => 0,
                ),
            196 =>
                array(
                    'id' => 197,
                    'shortname' => 'SK',
                    'name' => 'Slovakia',
                    'name_ar' => 'سلوفاكيا',
                    'phonecode' => 421,
                    'status' => 0,
                ),
            197 =>
                array(
                    'id' => 198,
                    'shortname' => 'SI',
                    'name' => 'Slovenia',
                    'name_ar' => 'سلوفينيا',
                    'phonecode' => 386,
                    'status' => 0,
                ),
            198 =>
                array(
                    'id' => 199,
                    'shortname' => 'XG',
                    'name' => 'Smaller Territories of the UK',
                    'name_ar' => 'الأراضي الصغيرة للمملكة المتحدة',
                    'phonecode' => 44,
                    'status' => 0,
                ),
            199 =>
                array(
                    'id' => 200,
                    'shortname' => 'SB',
                    'name' => 'Solomon Islands',
                    'name_ar' => 'جزر سليمان',
                    'phonecode' => 677,
                    'status' => 0,
                ),
            200 =>
                array(
                    'id' => 201,
                    'shortname' => 'SO',
                    'name' => 'Somalia',
                    'name_ar' => 'الصومال',
                    'phonecode' => 252,
                    'status' => 0,
                ),
            201 =>
                array(
                    'id' => 202,
                    'shortname' => 'ZA',
                    'name' => 'South Africa',
                    'name_ar' => 'جنوب أفريقيا',
                    'phonecode' => 27,
                    'status' => 0,
                ),
            202 =>
                array(
                    'id' => 203,
                    'shortname' => 'GS',
                    'name' => 'South Georgia',
                    'name_ar' => 'جورجيا الجنوبية',
                    'phonecode' => 0,
                    'status' => 0,
                ),
            203 =>
                array(
                    'id' => 204,
                    'shortname' => 'SS',
                    'name' => 'South Sudan',
                    'name_ar' => 'جنوب السودان',
                    'phonecode' => 211,
                    'status' => 0,
                ),
            204 =>
                array(
                    'id' => 205,
                    'shortname' => 'ES',
                    'name' => 'Spain',
                    'name_ar' => 'إسبانيا',
                    'phonecode' => 34,
                    'status' => 0,
                ),
            205 =>
                array(
                    'id' => 206,
                    'shortname' => 'LK',
                    'name' => 'Sri Lanka',
                    'name_ar' => 'سريلانكا',
                    'phonecode' => 94,
                    'status' => 0,
                ),
            206 =>
                array(
                    'id' => 207,
                    'shortname' => 'SD',
                    'name' => 'Sudan',
                    'name_ar' => 'السودان',
                    'phonecode' => 249,
                    'status' => 0,
                ),
            207 =>
                array(
                    'id' => 208,
                    'shortname' => 'SR',
                    'name' => 'Suriname',
                    'name_ar' => 'سورينام',
                    'phonecode' => 597,
                    'status' => 0,
                ),
            208 =>
                array(
                    'id' => 209,
                    'shortname' => 'SJ',
                    'name' => 'Svalbard And Jan Mayen Islands',
                    'name_ar' => 'سفالبارد وجزر جان ماين',
                    'phonecode' => 47,
                    'status' => 0,
                ),
            209 =>
                array(
                    'id' => 210,
                    'shortname' => 'SZ',
                    'name' => 'Swaziland',
                    'name_ar' => 'سوازيلاند',
                    'phonecode' => 268,
                    'status' => 0,
                ),
            210 =>
                array(
                    'id' => 211,
                    'shortname' => 'SE',
                    'name' => 'Sweden',
                    'name_ar' => 'السويد',
                    'phonecode' => 46,
                    'status' => 0,
                ),
            211 =>
                array(
                    'id' => 212,
                    'shortname' => 'CH',
                    'name' => 'Switzerland',
                    'name_ar' => 'سويسرا',
                    'phonecode' => 41,
                    'status' => 0,
                ),
            212 =>
                array(
                    'id' => 213,
                    'shortname' => 'SY',
                    'name' => 'Syria',
                    'name_ar' => 'سوريا',
                    'phonecode' => 963,
                    'status' => 0,
                ),
            213 =>
                array(
                    'id' => 214,
                    'shortname' => 'TW',
                    'name' => 'Taiwan',
                    'name_ar' => 'تايوان',
                    'phonecode' => 886,
                    'status' => 0,
                ),
            214 =>
                array(
                    'id' => 215,
                    'shortname' => 'TJ',
                    'name' => 'Tajikistan',
                    'name_ar' => 'طاجيكستان',
                    'phonecode' => 992,
                    'status' => 0,
                ),
            215 =>
                array(
                    'id' => 216,
                    'shortname' => 'TZ',
                    'name' => 'Tanzania',
                    'name_ar' => 'تنزانيا',
                    'phonecode' => 255,
                    'status' => 0,
                ),
            216 =>
                array(
                    'id' => 217,
                    'shortname' => 'TH',
                    'name' => 'Thailand',
                    'name_ar' => 'تايلاند',
                    'phonecode' => 66,
                    'status' => 0,
                ),
            217 =>
                array(
                    'id' => 218,
                    'shortname' => 'TG',
                    'name' => 'Togo',
                    'name_ar' => 'توغو',
                    'phonecode' => 228,
                    'status' => 0,
                ),
            218 =>
                array(
                    'id' => 219,
                    'shortname' => 'TK',
                    'name' => 'Tokelau',
                    'name_ar' => 'توكيلاو',
                    'phonecode' => 690,
                    'status' => 0,
                ),
            219 =>
                array(
                    'id' => 220,
                    'shortname' => 'TO',
                    'name' => 'Tonga',
                    'name_ar' => 'تونغا',
                    'phonecode' => 676,
                    'status' => 0,
                ),
            220 =>
                array(
                    'id' => 221,
                    'shortname' => 'TT',
                    'name' => 'Trinidad And Tobago',
                    'name_ar' => 'ترينيداد وتوباغو',
                    'phonecode' => 1868,
                    'status' => 0,
                ),
            221 =>
                array(
                    'id' => 222,
                    'shortname' => 'TN',
                    'name' => 'Tunisia',
                    'name_ar' => 'تونس',
                    'phonecode' => 216,
                    'status' => 0,
                ),
            222 =>
                array(
                    'id' => 223,
                    'shortname' => 'TR',
                    'name' => 'Turkey',
                    'name_ar' => 'تركيا',
                    'phonecode' => 90,
                    'status' => 0,
                ),
            223 =>
                array(
                    'id' => 224,
                    'shortname' => 'TM',
                    'name' => 'Turkmenistan',
                    'name_ar' => 'تركمانستان',
                    'phonecode' => 7370,
                    'status' => 0,
                ),
            224 =>
                array(
                    'id' => 225,
                    'shortname' => 'TC',
                    'name' => 'Turks And Caicos Islands',
                    'name_ar' => 'جزر تركس وكايكوس',
                    'phonecode' => 1649,
                    'status' => 0,
                ),
            225 =>
                array(
                    'id' => 226,
                    'shortname' => 'TV',
                    'name' => 'Tuvalu',
                    'name_ar' => 'توفالو',
                    'phonecode' => 688,
                    'status' => 0,
                ),
            226 =>
                array(
                    'id' => 227,
                    'shortname' => 'UG',
                    'name' => 'Uganda',
                    'name_ar' => 'أوغندا',
                    'phonecode' => 256,
                    'status' => 0,
                ),
            227 =>
                array(
                    'id' => 228,
                    'shortname' => 'UA',
                    'name' => 'Ukraine',
                    'name_ar' => 'أوكرانيا',
                    'phonecode' => 380,
                    'status' => 0,
                ),
            228 =>
                array(
                    'id' => 229,
                    'shortname' => 'AE',
                    'name' => 'United Arab Emirates',
                    'name_ar' => 'الإمارات العربية المتحدة',
                    'phonecode' => 971,
                    'status' => 0,
                ),
            229 =>
                array(
                    'id' => 230,
                    'shortname' => 'GB',
                    'name' => 'United Kingdom',
                    'name_ar' => 'المملكة المتحدة',
                    'phonecode' => 44,
                    'status' => 0,
                ),
            230 =>
                array(
                    'id' => 231,
                    'shortname' => 'US',
                    'name' => 'United States',
                    'name_ar' => 'الولايات المتحدة',
                    'phonecode' => 1,
                    'status' => 0,
                ),
            231 =>
                array(
                    'id' => 232,
                    'shortname' => 'UM',
                    'name' => 'United States Minor Outlying Islands',
                    'name_ar' => 'جزر الولايات المتحدة الصغيرة النائية',
                    'phonecode' => 1,
                    'status' => 0,
                ),
            232 =>
                array(
                    'id' => 233,
                    'shortname' => 'UY',
                    'name' => 'Uruguay',
                    'name_ar' => 'أوروغواي',
                    'phonecode' => 598,
                    'status' => 0,
                ),
            233 =>
                array(
                    'id' => 234,
                    'shortname' => 'UZ',
                    'name' => 'Uzbekistan',
                    'name_ar' => 'أوزبكستان',
                    'phonecode' => 998,
                    'status' => 0,
                ),
            234 =>
                array(
                    'id' => 235,
                    'shortname' => 'VU',
                    'name' => 'Vanuatu',
                    'name_ar' => 'فانواتو',
                    'phonecode' => 678,
                    'status' => 0,
                ),
            235 =>
                array(
                    'id' => 236,
                    'shortname' => 'VA',
                    'name' => 'Vatican City State (Holy See)',
                    'name_ar' => 'مدينة الفاتيكان',
                    'phonecode' => 39,
                    'status' => 0,
                ),
            236 =>
                array(
                    'id' => 237,
                    'shortname' => 'VE',
                    'name' => 'Venezuela',
                    'name_ar' => 'فنزويلا',
                    'phonecode' => 58,
                    'status' => 0,
                ),
            237 =>
                array(
                    'id' => 238,
                    'shortname' => 'VN',
                    'name' => 'Vietnam',
                    'name_ar' => 'فيتنام',
                    'phonecode' => 84,
                    'status' => 0,
                ),
            238 =>
                array(
                    'id' => 239,
                    'shortname' => 'VG',
                    'name' => 'Virgin Islands (British)',
                    'name_ar' => 'جزر العذراء البريطانية',
                    'phonecode' => 1284,
                    'status' => 0,
                ),
            239 =>
                array(
                    'id' => 240,
                    'shortname' => 'VI',
                    'name' => 'Virgin Islands (US)',
                    'name_ar' => 'جزر العذراء الأمريكية',
                    'phonecode' => 1340,
                    'status' => 0,
                ),
            240 =>
                array(
                    'id' => 241,
                    'shortname' => 'WF',
                    'name' => 'Wallis And Futuna Islands',
                    'name_ar' => 'جزر واليس وفوتونا',
                    'phonecode' => 681,
                    'status' => 0,
                ),
            241 =>
                array(
                    'id' => 242,
                    'shortname' => 'EH',
                    'name' => 'Western Sahara',
                    'name_ar' => 'الصحراء الغربية',
                    'phonecode' => 212,
                    'status' => 0,
                ),
            242 =>
                array(
                    'id' => 243,
                    'shortname' => 'YE',
                    'name' => 'Yemen',
                    'name_ar' => 'اليمن',
                    'phonecode' => 967,
                    'status' => 0,
                ),
        ));


    }

}
