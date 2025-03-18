<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\University\Models\University;//TODO module for university

class UniversitySeederTable extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $universities = [
            // North America - USA
            ['en' => 'Harvard University', 'ar' => 'جامعة هارفارد', 'country_code' => 'US'],
            ['en' => 'Stanford University', 'ar' => 'جامعة ستانفورد', 'country_code' => 'US'],
            ['en' => 'Massachusetts Institute of Technology (MIT)', 'ar' => 'معهد ماساتشوستس للتكنولوجيا', 'country_code' => 'US'],
            ['en' => 'California Institute of Technology (Caltech)', 'ar' => 'معهد كاليفورنيا للتكنولوجيا', 'country_code' => 'US'],
            ['en' => 'Princeton University', 'ar' => 'جامعة برينستون', 'country_code' => 'US'],
            ['en' => 'Yale University', 'ar' => 'جامعة ييل', 'country_code' => 'US'],
            ['en' => 'Columbia University', 'ar' => 'جامعة كولومبيا', 'country_code' => 'US'],
            ['en' => 'University of Chicago', 'ar' => 'جامعة شيكاغو', 'country_code' => 'US'],
            ['en' => 'University of Pennsylvania', 'ar' => 'جامعة بنسلفانيا', 'country_code' => 'US'],
            ['en' => 'Johns Hopkins University', 'ar' => 'جامعة جونز هوبكنز', 'country_code' => 'US'],
            ['en' => 'Northwestern University', 'ar' => 'جامعة نورث وسترن', 'country_code' => 'US'],
            ['en' => 'Duke University', 'ar' => 'جامعة ديوك', 'country_code' => 'US'],
            ['en' => 'University of California, Berkeley', 'ar' => 'جامعة كاليفورنيا، بيركلي', 'country_code' => 'US'],
            ['en' => 'University of California, Los Angeles (UCLA)', 'ar' => 'جامعة كاليفورنيا، لوس أنجلوس', 'country_code' => 'US'],
            ['en' => 'University of Michigan', 'ar' => 'جامعة ميشيغان', 'country_code' => 'US'],
            ['en' => 'Cornell University', 'ar' => 'جامعة كورنيل', 'country_code' => 'US'],

            // North America - Canada
            ['en' => 'University of Toronto', 'ar' => 'جامعة تورونتو', 'country_code' => 'CA'],
            ['en' => 'McGill University', 'ar' => 'جامعة ماكجيل', 'country_code' => 'CA'],
            ['en' => 'University of British Columbia', 'ar' => 'جامعة كولومبيا البريطانية', 'country_code' => 'CA'],
            ['en' => 'University of Montreal', 'ar' => 'جامعة مونتريال', 'country_code' => 'CA'],
            ['en' => 'University of Alberta', 'ar' => 'جامعة ألبرتا', 'country_code' => 'CA'],

            // United Kingdom
            ['en' => 'University of Oxford', 'ar' => 'جامعة أكسفورد', 'country_code' => 'GB'],
            ['en' => 'University of Cambridge', 'ar' => 'جامعة كامبريدج', 'country_code' => 'GB'],
            ['en' => 'Imperial College London', 'ar' => 'كلية إمبريال لندن', 'country_code' => 'GB'],
            ['en' => 'University College London', 'ar' => 'كلية جامعة لندن', 'country_code' => 'GB'],
            ['en' => 'London School of Economics and Political Science', 'ar' => 'كلية لندن للاقتصاد والعلوم السياسية', 'country_code' => 'GB'],
            ['en' => 'University of Edinburgh', 'ar' => 'جامعة إدنبرة', 'country_code' => 'GB'],
            ['en' => 'King\'s College London', 'ar' => 'كلية كينجز لندن', 'country_code' => 'GB'],

            // Europe
            ['en' => 'ETH Zurich', 'ar' => 'المعهد الفيدرالي السويسري للتكنولوجيا في زيورخ', 'country_code' => 'CH'],
            ['en' => 'Sorbonne University', 'ar' => 'جامعة السوربون', 'country_code' => 'FR'],
            ['en' => 'École Polytechnique', 'ar' => 'المدرسة متعددة التقنيات', 'country_code' => 'FR'],
            ['en' => 'Technical University of Munich', 'ar' => 'جامعة ميونخ التقنية', 'country_code' => 'DE'],
            ['en' => 'Heidelberg University', 'ar' => 'جامعة هايدلبرغ', 'country_code' => 'DE'],
            ['en' => 'Ludwig Maximilian University of Munich', 'ar' => 'جامعة لودفيغ ماكسيميليان في ميونخ', 'country_code' => 'DE'],
            ['en' => 'University of Amsterdam', 'ar' => 'جامعة أمستردام', 'country_code' => 'NL'],
            ['en' => 'KU Leuven', 'ar' => 'جامعة لوفان الكاثوليكية', 'country_code' => 'BE'],
            ['en' => 'University of Copenhagen', 'ar' => 'جامعة كوبنهاغن', 'country_code' => 'DK'],
            ['en' => 'Karolinska Institute', 'ar' => 'معهد كارولينسكا', 'country_code' => 'SE'],
            ['en' => 'University of Helsinki', 'ar' => 'جامعة هلسنكي', 'country_code' => 'FI'],
            ['en' => 'University of Oslo', 'ar' => 'جامعة أوسلو', 'country_code' => 'NO'],
            ['en' => 'University of Vienna', 'ar' => 'جامعة فيينا', 'country_code' => 'AT'],
            ['en' => 'University of Bologna', 'ar' => 'جامعة بولونيا', 'country_code' => 'IT'],
            ['en' => 'Sapienza University of Rome', 'ar' => 'جامعة سابينزا في روما', 'country_code' => 'IT'],
            ['en' => 'Complutense University of Madrid', 'ar' => 'جامعة كومبلوتنسي في مدريد', 'country_code' => 'ES'],
            ['en' => 'University of Barcelona', 'ar' => 'جامعة برشلونة', 'country_code' => 'ES'],
            ['en' => 'University of Lisbon', 'ar' => 'جامعة لشبونة', 'country_code' => 'PT'],
            ['en' => 'University of Warsaw', 'ar' => 'جامعة وارسو', 'country_code' => 'PL'],
            ['en' => 'Charles University', 'ar' => 'جامعة تشارلز', 'country_code' => 'CZ'],
            ['en' => 'Lomonosov Moscow State University', 'ar' => 'جامعة موسكو الحكومية', 'country_code' => 'RU'],
            ['en' => 'Saint Petersburg State University', 'ar' => 'جامعة سانت بطرسبرغ الحكومية', 'country_code' => 'RU'],

            // Asia - East Asia
            ['en' => 'Tsinghua University', 'ar' => 'جامعة تسينغهوا', 'country_code' => 'CN'],
            ['en' => 'Peking University', 'ar' => 'جامعة بكين', 'country_code' => 'CN'],
            ['en' => 'Fudan University', 'ar' => 'جامعة فودان', 'country_code' => 'CN'],
            ['en' => 'Shanghai Jiao Tong University', 'ar' => 'جامعة شنغهاي جياو تونغ', 'country_code' => 'CN'],
            ['en' => 'Zhejiang University', 'ar' => 'جامعة تشجيانغ', 'country_code' => 'CN'],
            ['en' => 'University of Tokyo', 'ar' => 'جامعة طوكيو', 'country_code' => 'JP'],
            ['en' => 'Kyoto University', 'ar' => 'جامعة كيوتو', 'country_code' => 'JP'],
            ['en' => 'Osaka University', 'ar' => 'جامعة أوساكا', 'country_code' => 'JP'],
            ['en' => 'Tohoku University', 'ar' => 'جامعة توهوكو', 'country_code' => 'JP'],
            ['en' => 'Seoul National University', 'ar' => 'جامعة سيول الوطنية', 'country_code' => 'KR'],
            ['en' => 'Korea Advanced Institute of Science and Technology (KAIST)', 'ar' => 'المعهد الكوري المتقدم للعلوم والتكنولوجيا', 'country_code' => 'KR'],
            ['en' => 'National Taiwan University', 'ar' => 'جامعة تايوان الوطنية', 'country_code' => 'TW'],
            ['en' => 'The University of Hong Kong', 'ar' => 'جامعة هونغ كونغ', 'country_code' => 'HK'],
            ['en' => 'The Chinese University of Hong Kong', 'ar' => 'جامعة هونغ كونغ الصينية', 'country_code' => 'HK'],
            ['en' => 'Hong Kong University of Science and Technology', 'ar' => 'جامعة هونغ كونغ للعلوم والتكنولوجيا', 'country_code' => 'HK'],
            ['en' => 'National University of Singapore', 'ar' => 'جامعة سنغافورة الوطنية', 'country_code' => 'SG'],
            ['en' => 'Nanyang Technological University', 'ar' => 'جامعة نانيانغ التكنولوجية', 'country_code' => 'SG'],

            // Asia - South & Southeast Asia
            ['en' => 'Indian Institute of Science', 'ar' => 'المعهد الهندي للعلوم', 'country_code' => 'IN'],
            ['en' => 'Indian Institute of Technology Bombay', 'ar' => 'المعهد الهندي للتكنولوجيا بومباي', 'country_code' => 'IN'],
            ['en' => 'Indian Institute of Technology Delhi', 'ar' => 'المعهد الهندي للتكنولوجيا دلهي', 'country_code' => 'IN'],
            ['en' => 'University of Delhi', 'ar' => 'جامعة دلهي', 'country_code' => 'IN'],
            ['en' => 'University of Malaya', 'ar' => 'جامعة ملايا', 'country_code' => 'MY'],
            ['en' => 'Universiti Putra Malaysia', 'ar' => 'جامعة بوترا ماليزيا', 'country_code' => 'MY'],
            ['en' => 'Chulalongkorn University', 'ar' => 'جامعة شولالونغكورن', 'country_code' => 'TH'],
            ['en' => 'University of Indonesia', 'ar' => 'جامعة إندونيسيا', 'country_code' => 'ID'],
            ['en' => 'University of the Philippines', 'ar' => 'جامعة الفلبين', 'country_code' => 'PH'],
            ['en' => 'Vietnam National University, Hanoi', 'ar' => 'جامعة فيتنام الوطنية، هانوي', 'country_code' => 'VN'],

            // Middle East & North Africa
            ['en' => 'King Abdulaziz University', 'ar' => 'جامعة الملك عبد العزيز', 'country_code' => 'SA'],
            ['en' => 'King Saud University', 'ar' => 'جامعة الملك سعود', 'country_code' => 'SA'],
            ['en' => 'King Abdullah University of Science and Technology', 'ar' => 'جامعة الملك عبد الله للعلوم والتقنية', 'country_code' => 'SA'],
            ['en' => 'King Fahd University of Petroleum and Minerals', 'ar' => 'جامعة الملك فهد للبترول والمعادن', 'country_code' => 'SA'],
            ['en' => 'Qatar University', 'ar' => 'جامعة قطر', 'country_code' => 'QA'],
            ['en' => 'United Arab Emirates University', 'ar' => 'جامعة الإمارات العربية المتحدة', 'country_code' => 'AE'],
            ['en' => 'American University of Sharjah', 'ar' => 'الجامعة الأمريكية في الشارقة', 'country_code' => 'AE'],
            ['en' => 'Khalifa University', 'ar' => 'جامعة خليفة', 'country_code' => 'AE'],
            ['en' => 'Kuwait University', 'ar' => 'جامعة الكويت', 'country_code' => 'KW'],
            ['en' => 'University of Jordan', 'ar' => 'الجامعة الأردنية', 'country_code' => 'JO'],
            ['en' => 'American University of Beirut', 'ar' => 'الجامعة الأمريكية في بيروت', 'country_code' => 'LB'],
            ['en' => 'Cairo University', 'ar' => 'جامعة القاهرة', 'country_code' => 'EG'],
            ['en' => 'Ain Shams University', 'ar' => 'جامعة عين شمس', 'country_code' => 'EG'],
            ['en' => 'Alexandria University', 'ar' => 'جامعة الإسكندرية', 'country_code' => 'EG'],
            ['en' => 'Al-Azhar University', 'ar' => 'جامعة الأزهر', 'country_code' => 'EG'],
            ['en' => 'University of Tunis', 'ar' => 'جامعة تونس', 'country_code' => 'TN'],
            ['en' => 'University of Algiers', 'ar' => 'جامعة الجزائر', 'country_code' => 'DZ'],
            ['en' => 'Mohammed V University', 'ar' => 'جامعة محمد الخامس', 'country_code' => 'MA'],
            ['en' => 'University of Baghdad', 'ar' => 'جامعة بغداد', 'country_code' => 'IQ'],
            ['en' => 'University of Tehran', 'ar' => 'جامعة طهران', 'country_code' => 'IR'],
            ['en' => 'Tel Aviv University', 'ar' => 'جامعة تل أبيب', 'country_code' => 'IL'],
            ['en' => 'Hebrew University of Jerusalem', 'ar' => 'الجامعة العبرية في القدس', 'country_code' => 'IL'],
            ['en' => 'Istanbul University', 'ar' => 'جامعة إسطنبول', 'country_code' => 'TR'],
            ['en' => 'Bogazici University', 'ar' => 'جامعة بوغازيتشي', 'country_code' => 'TR'],
            ['en' => 'Middle East Technical University', 'ar' => 'جامعة الشرق الأوسط التقنية', 'country_code' => 'TR'],

            // Australia & New Zealand
            ['en' => 'University of Melbourne', 'ar' => 'جامعة ملبورن', 'country_code' => 'AU'],
            ['en' => 'Australian National University', 'ar' => 'الجامعة الوطنية الأسترالية', 'country_code' => 'AU'],
            ['en' => 'University of Sydney', 'ar' => 'جامعة سيدني', 'country_code' => 'AU'],
            ['en' => 'University of Queensland', 'ar' => 'جامعة كوينزلاند', 'country_code' => 'AU'],
            ['en' => 'University of New South Wales', 'ar' => 'جامعة نيو ساوث ويلز', 'country_code' => 'AU'],
            ['en' => 'Monash University', 'ar' => 'جامعة موناش', 'country_code' => 'AU'],
            ['en' => 'University of Auckland', 'ar' => 'جامعة أوكلاند', 'country_code' => 'NZ'],
            ['en' => 'University of Otago', 'ar' => 'جامعة أوتاغو', 'country_code' => 'NZ'],

            // Africa
            ['en' => 'University of Cape Town', 'ar' => 'جامعة كيب تاون', 'country_code' => 'ZA'],
            ['en' => 'University of the Witwatersrand', 'ar' => 'جامعة ويتواترسراند', 'country_code' => 'ZA'],
            ['en' => 'Stellenbosch University', 'ar' => 'جامعة ستيلينبوش', 'country_code' => 'ZA'],
            ['en' => 'University of Nairobi', 'ar' => 'جامعة نيروبي', 'country_code' => 'KE'],
            ['en' => 'University of Ghana', 'ar' => 'جامعة غانا', 'country_code' => 'GH'],
            ['en' => 'University of Ibadan', 'ar' => 'جامعة إبادان', 'country_code' => 'NG'],
            ['en' => 'University of Lagos', 'ar' => 'جامعة لاغوس', 'country_code' => 'NG'],
            ['en' => 'Addis Ababa University', 'ar' => 'جامعة أديس أبابا', 'country_code' => 'ET'],
            ['en' => 'University of Khartoum', 'ar' => 'جامعة الخرطوم', 'country_code' => 'SD'],

            // Latin America
            ['en' => 'University of São Paulo', 'ar' => 'جامعة ساو باولو', 'country_code' => 'BR'],
            ['en' => 'State University of Campinas', 'ar' => 'جامعة ولاية كامبيناس', 'country_code' => 'BR'],
            ['en' => 'Federal University of Rio de Janeiro', 'ar' => 'الجامعة الفيدرالية في ريو دي جانيرو', 'country_code' => 'BR'],
            ['en' => 'National Autonomous University of Mexico', 'ar' => 'الجامعة الوطنية المستقلة في المكسيك', 'country_code' => 'MX'],
            ['en' => 'Tecnológico de Monterrey', 'ar' => 'معهد مونتيري للتكنولوجيا', 'country_code' => 'MX'],
            ['en' => 'University of Buenos Aires', 'ar' => 'جامعة بوينس آيرس', 'country_code' => 'AR'],
            ['en' => 'University of Chile', 'ar' => 'جامعة تشيلي', 'country_code' => 'CL'],
            ['en' => 'Pontifical Catholic University of Chile', 'ar' => 'الجامعة البابوية الكاثوليكية في تشيلي', 'country_code' => 'CL'],
            ['en' => 'National University of Colombia', 'ar' => 'الجامعة الوطنية في كولومبيا', 'country_code' => 'CO'],
            ['en' => 'University of the Andes, Colombia', 'ar' => 'جامعة الأنديز، كولومبيا', 'country_code' => 'CO'],
            ['en' => 'University of Costa Rica', 'ar' => 'جامعة كوستاريكا', 'country_code' => 'CR'],
            ['en' => 'University of Havana', 'ar' => 'جامعة هافانا', 'country_code' => 'CU'],
            ['en' => 'University of the West Indies', 'ar' => 'جامعة جزر الهند الغربية', 'country_code' => 'JM'],
        ];

        foreach ($universities as $university) {
            University::firstOrCreate(
                [
                    'name->en' => $university['en'],
                    'country_code' => $university['country_code']
                ],
                [
                    'name' => [
                        'en' => $university['en'],
                        'ar' => $university['ar']
                    ]
                ]
            );
        }
    }
}
