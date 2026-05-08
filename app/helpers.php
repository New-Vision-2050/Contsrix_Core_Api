<?php

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Container\Attributes\Tag;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request;
use Intervention\Image\Image as ResizeImage;
use Modules\Company\CompanyCore\Models\Company;

;

use Modules\Country\Models\City;
use Modules\Country\Models\Country;
use Modules\Country\Models\State;
use Modules\RoleAndPermission\Models\Permission;
use Modules\User\Models\User;
use Stevebauman\Location\Facades\Location;
use Vectorface\Whip\Whip;


function validSiteUrl($site_referer, $url)
{
    $urlCheck = '/^(https?:\/\/)?(www\.)?' . $site_referer . '\/[a-zA-Z0-9(\.\?)?]/';
    if (preg_match($urlCheck, $url) == 1) {
        return 1;
    } else {
        return 0;
    }
}

function getTagsID($type = "job_grades")
{
    $array = Tag::where('type', $type)->pluck('name', 'id')->toArray();
    return $array;
}

function createCSV($csvData)
{
    $output = fopen('php://temp', 'r+');
    foreach ($csvData as $row) {
        fputcsv($output, $row);
    }
    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);
    return $csv;
}
function getPhoneNumberInfo(string $phone): array
{
    $phoneArray = explode(' ', $phone);
    return [
        'phone_code' => str_replace("+", "", $phoneArray[0]),
        'phone' => str_replace(" ","",$phone),
    ];
}

function splitName($name)
{
    $name = trim($name);
    $last_name = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
    $first_name = trim(preg_replace('#' . preg_quote($last_name, '#') . '#', '', $name));
    return array($first_name, $last_name);
}

function fullName($name)
{
    $parts = array();

    while (strlen(trim($name)) > 0) {
        $name = trim($name);
        $string = preg_replace('#.*\s([\w-]*)$#', '$1', $name);
        $parts[] = $string;
        $name = trim(preg_replace('#' . preg_quote($string, '#') . '#', '', $name));
    }

    if (empty($parts)) {
        return false;
    }

    $parts = array_reverse($parts);
    $name = array();
    $name['first_name'] = $parts[0];
    $name['middle_name'] = (isset($parts[2])) ? $parts[1] : '';
    $name['last_name'] = (isset($parts[2])) ? $parts[2] : (isset($parts[1]) ? $parts[1] : '');

    return $name;
}

function getBrowserLocale()
{
    // Credit: https://gist.github.com/Xeoncross/dc2ebf017676ae946082
    $websiteLanguages = ['EN', 'AR'];
    // Parse the Accept-Language according to:
    // http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4
    preg_match_all(
        '/([a-z]{1,8})' . // M1 - First part of language e.g en
        '(-[a-z]{1,8})*\s*' . // M2 -other parts of language e.g -us
        // Optional quality factor M3 ;q=, M4 - Quality Factor
        '(;\s*q\s*=\s*((1(\.0{0,3}))|(0(\.[0-9]{0,3}))))?/i',
        $_SERVER['HTTP_ACCEPT_LANGUAGE'],
        $langParse
    );

    $langs = $langParse[1]; // M1 - First part of language
    $quals = $langParse[4]; // M4 - Quality Factor

    $numLanguages = count($langs);
    $langArr = array();

    for ($num = 0; $num < $numLanguages; $num++) {
        $newLang = strtoupper($langs[$num]);
        $newQual = isset($quals[$num]) ? (empty($quals[$num]) ? 1.0 : floatval($quals[$num])) : 0.0;

        // Choose whether to upgrade or set the quality factor for the
        // primary language.
        $langArr[$newLang] = (isset($langArr[$newLang])) ?
            max($langArr[$newLang], $newQual) : $newQual;
    }

    // sort list based on value
    // langArr will now be an array like: array('EN' => 1, 'ES' => 0.5)
    arsort($langArr, SORT_NUMERIC);

    // The languages the client accepts in order of preference.
    $acceptedLanguages = array_keys($langArr);

    // Set the most preferred language that we have a translation for.
    foreach ($acceptedLanguages as $preferredLanguage) {
        if (in_array($preferredLanguage, $websiteLanguages)) {
            $_SESSION['lang'] = $preferredLanguage;
            return strtolower($preferredLanguage);
        }
    }
}

// function to sum
function sum($f, $s)
{
    $sum = doubleval($f) + doubleval($s);
    return round($sum, 3);
}

function sumInt($f, $s)
{
    return (int)$f + (int)$s;
}

// function to sub
function sub($f, $s)
{
    $sub = doubleval($f) - doubleval($s);
    return round($sub, 3);
}

function subInt($f, $s)
{
    return (int)$f - (int)$s;
}

// function to division
function division($f, $s)
{
    $division = doubleval($f) / doubleval($s);
    return round($division, 3);
}

function divisionFine($f, $s)
{
    $division = doubleval($f) / doubleval($s);
    return round($division, 5);
}

function divisionInt($f, $s)
{
    return (int)$f / (int)$s;
}

function percent($f)
{
    return division($f, 100);
}

function percentPayment($f)
{
    $percent = doubleval($f) / 100;
    return round($percent, 5);
}

// function to multiple
function multiple($f, $s)
{
    $multiple = doubleval($f) * doubleval($s);
    return round($multiple, 3);
}

function multipleInt($f, $s)
{
    return (int)$f * (int)$s;
}

// function to modulus
function modulus($f, $s)
{
    $modulus = doubleval($f) % doubleval($s);
    return round($modulus, 3);
}

function modulusInt($f, $s)
{
    $modulus = (int)$f % (int)$s;
    return (int)$modulus;
}

function urlGetContents($Url)
{
    if (!function_exists('curl_init')) {
        die('CURL is not installed!');
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $Url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

function hourType($all = "yes")
{
    $array = array(
        '' => __('اختر الساعة'),
        '06:00:00' => "06:00",
        '06:30:00' => "06:30",
        '07:00:00' => "07:00",
        '07:30:00' => "07:30",
        '08:00:00' => "08:00",
        '08:30:00' => "08:30",
        '09:00:00' => "09:00",
        '09:30:00' => "09:30",
        '10:00:00' => "10:00",
        '10:30:00' => "10:30",
        '11:00:00' => "12:00",
        '11:30:00' => "12:30",
        '12:00:00' => "12:00",
        '12:30:00' => "12:30",
        '13:00:00' => "13:00",
        '13:30:00' => "13:30",
        '14:00:00' => "14:00",
        '14:30:00' => "14:30",
        '15:00:00' => "15:00",
        '15:30:00' => "15:30",
        '16:00:00' => "16:00",
        '16:30:00' => "16:30",
        '17:00:00' => "17:00",
        '17:30:00' => "17:30",
        '18:00:00' => "18:00",
        '18:30:00' => "18:30",
        '19:00:00' => "19:00",
        '19:30:00' => "19:30",
        '20:00:00' => "20:00",
        '20:30:00' => "20:30",
        '21:00:00' => "21:00",
        '21:30:00' => "21:30",
        '22:00:00' => "22:00",
        '22:30:00' => "22:30",
        '23:00:00' => "23:00",
        '23:30:00' => "23:30",
        '00:00:00' => "00:00",
        '00:30:00' => "00:30",
        '01:00:00' => "01:00",
        '01:30:00' => "01:30",
        '02:00:00' => "02:00",
        '02:30:00' => "02:30",
        '03:00:00' => "03:00",
        '03:30:00' => "03:30",
        '04:00:00' => "04:00",
        '04:30:00' => "04:30",
        '05:00:00' => "05:00",
        '05:30:00' => "05:30",
    );
    if ($all == "no") {
        unset($array['']);
    }
    return $array;
}

function designTypes()
{
    return [
        "circle" => "شكل دائري",
        "hexagon" => "شكل سداسي",
        "half_circle" => "نصف دائري",
        "cards" => "كروت",
    ];
}

function pageType($all = "no")
{
    $array = array(
        'all' => __('All'),
        'home' => __('Home'),
        'about' => __('About'),
        'contact' => __('Contact'),
        'contact_form' => __('Contact Form'),
        'terms' => __('Terms'),
        'privacy' => __('Privacy'),
        'faq' => __('Faq'),
        'branch' => __('Branch'),
        'profile' => __('Profile'),
        'team' => __('Team'),
        'mission' => __('Mission'),
        'vision' => __('Vision'),
        'value' => __('Value'),
        'address' => __('Address'),
        'work_time' => __('Work Time'),
        'appointment' => __("Appointment"),
        'feature' => __("Feature"),
        'gift' => __("Gift"),
        'goal' => __("Goal"),
        'testimonial' => __("Testimonial"),
        'service' => __("Service"),
        'post' => __("Post"),
        'category' => __("Category"),
        'copyright' => __('Copy Right'),
        'social' => __('Social'),
        'slider' => __('Slider'),
        'header' => __('Header'),
        'footer' => __('footer'),
    );
    if ($all == "no") {
        unset($array['all']);
    }
    return $array;
}

function notifiType()
{
    $array = array(
        'all' => __('All'),
        'firebase' => __('firebase'),
        'database' => __('database'),
    );
    return $array;
}

function moneyType($all = "no")
{
    $array = array(
        '' => __('-'),
        'day' => __('يوم'),
        'week' => __('اسبوع'),
        'month' => __('شهر'),
        'year' => __('سنة'),
        'project' => __('مشروع'),
        'task' => __('مهمة'),
    );
    if ($all != "yes") {
        unset($array['']);
    }
    return $array;
}

function currencyType($all = "no")
{
    $array = array(
        '' => __('-'),
        'EGP' => __('جنيه مصري'),
        'SAR' => __('ريال سعودي'),
        'USD' => __('دولار امريكي'),
    );
    if ($all != "yes") {
        unset($array['']);
    }
    return $array;
}

function vacationType($all = "no")
{
    $array = array(
        '' => __('None'),
        'work' => __('Work'),
        'sick' => __('Sick'),
        'entertainment' => __('Entertainment'),
    );
    if ($all != "yes") {
        unset($array['']);
    }
    return $array;
}

function supportType($all = "no")
{
    $array = array(
        '' => __('None'),
        'username' => __('Change User Name'),
        'password' => __('Change Password'),
    );
    if ($all != "yes") {
        unset($array['']);
    }
    return $array;
}

function branchType($all = "no")
{
    $array = array(
        '' => __('النوع'),
        'central' => __('مركزي'),
        'main' => __('رئيسي'),
        'sub' => __('فرعي'),
        // 'other' => __('Other'),
    );
    if ($all != "yes") {
        unset($array['']);
    }
    return $array;
}

function getTimeZoneByRequest()
{
     $position = Location::get(request()->ip());

     $timezone = $position ? $position->timezone : "Asia/Riyadh";
    return $timezone ?? "Asia/Riyadh";
}
function getTimeZoneBranchByRequest()
{
    return once(function () {
        if (! auth()->check()) {
            return config('app.timezone');
        }

        $user = User::query()
            ->where('id', auth()->id())
            ->with([
                'userProfessionalData',
                'userProfessionalData.branch',
                'userProfessionalData.branch.address',
                'userProfessionalData.branch.address.country',
                'userProfessionalData.branch.address.country.timezones',
            ])
            ->first();

        $timezone = $user?->userProfessionalData?->branch?->address?->country?->timezones;

        return $timezone[0]['zoneName'] ?? 'Asia/Riyadh';
    });
}

function priorityType($all = "yes")
{
    $array = array(
        '' => __('None'),
        'low' => __('Low'),
        'normal' => __('Normal'),
        'high' => __('High'),
    );
    if ($all != "yes") {
        unset($array['']);
    }
    return $array;
}

function NotificationsModel($type = "")
{
    $array = array(
        'admin' => "AdminNotification",
        'orders' => "OrderNotification",
        'services' => "ServiceNotification",
    );
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return $array["admin"];
    }
}

function getModuleTableModelName($type)
{
    $array = array(
        'activity_logs' => 'Activity Log',
        'branches' => 'Branch',
        'categories' => 'Category',
        'contacts' => 'Contact',
        'users' => 'User',
        'services' => 'Service',
        'posts' => 'Post',
        'pages' => 'Page',
        'orders' => 'Order',
        'logs' => 'Log',
        'settings' => 'Setting',
        'roles' => 'Role',
        'translations' => 'Translation',
        'tables' => 'Table View',
        'images' => 'Image',
        'notifications' => 'Notification',
        'dashboard' => 'Dashboard',
    );
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return $type;
    }
}

function weekDays($all = "no")
{

    $array = array(
        '' => __('All'),
        'Sat' => __('Saturday'),
        'Sun' => __('Sunday'),
        'Mon' => __('Monday'),
        'Tue' => __('Tuesday'),
        'Wed' => __('Wednesday'),
        'Thu' => __('Thursday'),
        'Fri' => __('Friday'),
    );
    if ($all != "yes") {
        unset($array['']);
    }
    return $array;
}

function weekDaysNum()
{
    $array = array(
        1 => 'Sat',
        2 => 'Sun',
        3 => 'Mon',
        4 => 'Tue',
        5 => 'Wed',
        6 => 'Thu',
        7 => 'Fri',
    );

    return $array;
}

function userType($all = "yes", $is_search = "no")
{
    $array = array(
        '' => __('None'),
        'admin' => __('Admin'),
        'manger' => __('Manger'),
        'account' => __('Account'),
        'account_manger' => __('Account Manger'),
        'account_admin' => __('Account Admin'),
        'office' => __('Office'),
        'office_manger' => __('Office Manger'),
        'office_admin' => __('Office Admin'),
    );
    if ($all != "yes") {
        unset($array['admin']);
    }
    if ($is_search != "yes") {
        unset($array['']);
    }
    return $array;
}

function nationalType($all = "no")
{
    $array = array(
        '' => __('None'),
        'card' => __('Card'),
        'passport' => __('Passport'),
    );
    if ($all == "no") {
        unset($array['']);
    }
    return $array;
}


function notificationsType()
{
    $array = array(
        'admin' => "admin",
        'orders' => "orders",
        'services' => "services",
    );
    return $array;
}


function notificationsTypeModel()
{
    $array = array(
        'admin' => __(config('app.name')),
        'orders' => __("Order"),
        'services' => __("Service"),
    );
    return $array;
}

function filterType($all = "no")
{
    $array = array(
        '' => __('None'),
        'text' => ('Text'),
        'select' => ('Select'),
        'datepicker' => ('Date'),
        'number' => __('Number'),
    );
    if ($all != "yes") {
        unset($array['']);
    }
    return $array;
}


function paymentYear($all = "years")
{
    $array = array(
        1 => "1",
        2 => "2",
        3 => "3",
        4 => "4",
        5 => "5",
        6 => "6",
        7 => "7",
        8 => "8",
        9 => "9",
        10 => "10",
        11 => "11",
        12 => "12",
    );
    return $array;
}

function ratingStatus($all = "yes")
{
    $array = array(
        0 => __('None'),
        1 => "1",
        2 => "2",
        3 => "3",
        4 => "4",
        5 => "5",
    );
    if ($all != "yes") {
        unset($array['']);
    }
    return $array;
}


function digitalType($all = "yes")
{
    $array = array(
        '' => __('None'),
        'facebook' => __('facebook'),
        'google' => __('google'),
        'instagram' => __('instagram'),
        'website' => __('website'),
    );
    if ($all != "yes") {
        unset($array['']);
    }
    return $array;
}


function userName($type = "")
{
    $array = array(
        'admin' => __('Admin'),
        'manger' => __('Manger'),
        'account' => __('Account'),
        'account_manger' => __('Account Manger'),
        'account_admin' => __('Account Admin'),
        'office' => __('Office'),
        'office_manger' => __('Office Manger'),
        'office_admin' => __('Office Admin'),
    );
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return $type;
    }
}


function walletType()
{
    $array = array(
        'income' => __('Income'),
        'outcome' => __('Outcome'),

    );
    return $array;
}

function deviceType($all = "no")
{
    $array = array(
        'all' => __('All'),
        'android' => __('Android'),
        'ios' => __('IOS'),
        // 'huawei'     => __('huawei')
        // 'windows' => __('windows'),
    );
    if ($all == "no") {
        unset($array['all']);
    }
    return $array;
}

function socialType($all = "no")
{
    $array = array(
        '' => __('All'),
        'facebook' => __('facebook'),
        'google' => __('google'),
        'instagram' => __('instagram'),
        'twitter' => __('twitter'),
    );
    if ($all == "no") {
        unset($array['']);
    }
    return $array;
}

function couponType($all = "no")
{
    $array = array(
        '' => __('ALL'),
        'fixed' => __('Fixed'),
        'percentage' => __('Percentage'),
    );
    if ($all == "no") {
        unset($array['']);
    }
    return $array;
}

function monthType()
{

    $array = array(
        "Jan" => __('January'),
        "Feb" => __('February'),
        "Mar" => __('March'),
        "Apr" => __('April'),
        "May" => __('May'),
        "Jun" => __('June'),
        "Jul" => __('July'),
        "Aug" => __('August'),
        "Sep" => __('September'),
        "Oct" => __('October'),
        "Nov" => __('November'),
        "Dec" => __('December'),
    );
    return $array;
}

function paymentType($all = "no")
{
    $array = array(
        '' => __('None'),
        'cash' => __('Cash'),
        'bank' => __('Bank'),
        'check' => __('Check'),
        'bank_transfer' => __('Bank Transfer'),
    );
    if ($all == "no") {
        unset($array['']);
    }
    return $array;
}

function userTypeAdmin()
{
    $array = array(
        'manger' => __('Manger'),
        'admin' => __('Admin'),
    );
    return $array;
}

function languageType()
{

    $array = array(
        'ar' => __('Arabic'),
        'en' => __('English'),
    );
    return $array;
}

function addressType()
{

    $array = array(
        'house' => __('House'),
        'work' => __('work'),
        'other' => __('Other'),
    );
    return $array;
}

function genderType($all = "yes")
{

    $array = array(
        '' => __('اختر النوع'),
        'male' => __('ذكر'),
        'female' => __('انثي'),
    );
    if ($all != "yes") {
        unset($array['']);
    }
    return $array;
}

function tableView($all = "no", $other = "yes")
{
    $array = array(
        25 => '25',
        50 => '50',
        100 => '100',
        250 => '250',
        500 => '500',
    );
    return $array;
}

function statusDefaultType()
{
    $array = array(
        0 => __('In Active'),
        1 => __('Active'),
    );
    return $array;
}

function statusType($all = "no")
{
    $array = array(
        -1 => __('إختر الحالة'),
        1 => __('نشط'),
        0 => __('غير نشط'),
    );
    if ($all == "no") {
        unset($array[-1]);
    }
    return $array;
}

function statusShow($all = "no")
{
    $array = array(
        -1 => __('All'),
        1 => __('Yes'),
        0 => __('No'),
    );
    if ($all == "no") {
        unset($array[-1]);
    }
    return $array;
}


function orderType($all = "yes")
{
    $array = array(
        '' => __('Order Type'),
        "DESC" => "DESC",
        "ASC" => "ASC"
    );
    if ($all == "no") {
        unset($array['']);
    }
    return $array;
}

function statusShowDefault($all = "no")
{
    $array = array(
        -1 => __('All'),
        0 => __('No'),
        1 => __('Yes'),
    );
    if ($all == "no") {
        unset($array[-1]);
    }
    return $array;
}

function showTypeDefault($all = "no")
{
    $array = array(
        "" => __('All'),
        "no" => __('No'),
        "yes" => __('Yes'),
    );
    if ($all == "no") {
        unset($array[""]);
    }
    return $array;
}

function orderStatus($all = "no")
{
    $array = array(
        "" => __('All'),
        "request" => __('Request'),
        "approve" => __('Approve'),
        "pending" => __('Pending'),
        "finish" => __('Finish'),
        "cancel" => __('Cancel'),
    );
    if ($all == "no") {
        unset($array[""]);
    }
    return $array;
}

function showTrashedDefault($all = "no")
{
    $array = array(
        "" => __('All'),
        "no" => __('No Trashed'),
        "yes" => __('With Trashed'),
    );
    if ($all == "no") {
        unset($array[""]);
    }
    return $array;
}

function showType($all = "no")
{
    $array = array(
        '' => __('All'),
        'yes' => __('Yes'),
        'no' => __('No'),
    );
    if ($all == "no") {
        unset($array['']);
    }
    return $array;
}

function languageName($type = "")
{
    $array = array(
        'ar' => __('Arabic'),
        'en' => __('English'),
    );

    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return $type;
    }
}

function genderName($type = null)
{
    $array = array(
        'male' => __('male'),
        'female' => __('female'),
    );

    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return $type;
    }
}

function statusName($type = null)
{
    $array = array(
        -1 => __('All'),
        1 => __('Active'),
        0 => __('In Active'),
    );

    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return $type;
    }
}


function statusShowName($type = null)
{
    $array = array(
        -1 => __('All'),
        1 => __('Yes'),
        0 => __('No'),
    );

    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return $type;
    }
}

function couponName($type)
{
    $array = array(
        'percentage' => __('Percentage'),
        'fixed' => __('Fixed'),
    );
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return $type;
    }
}

function priorityName($type)
{
    $array = array(
        'low' => __('Low'),
        'normal' => __('Normal'),
        'high' => __('High'),
    );
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return $type;
    }
}

function branchName($type)
{
    $array = array(
        '' => __('غير محدد'),
        'main' => __('أساسى'),
        'sub' => __('فرعى'),
        'other' => __('أخرى'),
    );
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return $type;
    }
}

function ClientStatus($type)
{
    $array = array(
        '' => __('غير محدد'),
        'main' => __('جارى العمل'),
        'sub' => __('منتهى'),
    );
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return $type;
    }
}

function deviceName($type)
{
    $array = array(
        'android' => __('Android'),
        'ios' => __('IOS'),
        // 'huawei'     => __('huawei')
        // 'windows' => __('windows'),
    );
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return $type;
    }
}

function walletName($type)
{
    $array = array(
        'income' => __('Income'),
        'outcome' => __('Outcome'),

    );
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return $type;
    }
}

function paymentName($type)
{
    $array = array(
        'cash' => __('Cash'),
        'bank' => __('Bank'),
        'check' => __('Check'),
        'bank_transfer' => __('Bank Transfer'),
        'paypal' => __('Paypal'),
        'visa' => __('Visa'),
        'master_card' => __('Master Card'),
        'mada' => __('Mada'),
        'apple_pay' => __('Apple Pay'),
        'google_pay' => __('Google Pay'),
    );
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return $type;
    }
}

function addressName($type)
{

    $array = array(
        'house' => __('House'),
        'work' => __('work'),
        'other' => __('Other'),
    );
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return $type;
    }
}

function socialName($type = "")
{
    $array = array(
        'facebook' => __('facebook'),
        'instagram' => __('instagram'),
        'google' => __('google'),
        'twitter' => __('twitter'),
    );
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return $type;
    }
}

function monthName($type = "")
{

    $array = array(
        "Jan" => __('January'),
        "Feb" => __('February'),
        "Mar" => __('March'),
        "Apr" => __('April'),
        "May" => __('May'),
        "Jun" => __('June'),
        "Jul" => __('July'),
        "Aug" => __('August'),
        "Sep" => __('September'),
        "Oct" => __('October'),
        "Nov" => __('November'),
        "Dec" => __('December'),
    );
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return $type;
    }
}

function weekName($type = "")
{

    $array = array(
        'Sat' => __('السبت'),
        'Sun' => __('الاحد'),
        'Mon' => __('الاثنين'),
        'Tue' => __('الثلاثاء'),
        'Wed' => __('الاربعاء'),
        'Thu' => __('الخميس'),
        'Fri' => __('الجمعة'),
    );
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return $type;
    }
}

function digitalName($type = "")
{
    $array = array(
        // ''          => __('None'),
        'facebook' => __('facebook'),
        'google' => __('google'),
        'instagram' => __('instagram'),
        'website' => __('website'),
    );
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return $type;
    }
}

function countries($all = "yes")
{
    $array = array(
        "" => __("None"),
        "AF" => "Afghanistan",
        "AX" => "Åland Islands",
        "AL" => "Albania",
        "DZ" => "Algeria",
        "AS" => "American Samoa",
        "AD" => "Andorra",
        "AO" => "Angola",
        "AI" => "Anguilla",
        "AQ" => "Antarctica",
        "AG" => "Antigua and Barbuda",
        "AR" => "Argentina",
        "AM" => "Armenia",
        "AW" => "Aruba",
        "AU" => "Australia",
        "AT" => "Austria",
        "AZ" => "Azerbaijan",
        "BS" => "Bahamas",
        "BH" => "Bahrain",
        "BD" => "Bangladesh",
        "BB" => "Barbados",
        "BY" => "Belarus",
        "BE" => "Belgium",
        "BZ" => "Belize",
        "BJ" => "Benin",
        "BM" => "Bermuda",
        "BT" => "Bhutan",
        "BO" => "Bolivia",
        "BA" => "Bosnia and Herzegovina",
        "BW" => "Botswana",
        "BV" => "Bouvet Island",
        "BR" => "Brazil",
        "IO" => "British Indian Ocean Territory",
        "BN" => "Brunei Darussalam",
        "BG" => "Bulgaria",
        "BF" => "Burkina Faso",
        "BI" => "Burundi",
        "KH" => "Cambodia",
        "CM" => "Cameroon",
        "CA" => "Canada",
        "CV" => "Cape Verde",
        "KY" => "Cayman Islands",
        "CF" => "Central African Republic",
        "TD" => "Chad",
        "CL" => "Chile",
        "CN" => "China",
        "CX" => "Christmas Island",
        "CC" => "Cocos (Keeling) Islands",
        "CO" => "Colombia",
        "KM" => "Comoros",
        "CG" => "Congo",
        "CD" => "Congo, The Democratic Republic of The",
        "CK" => "Cook Islands",
        "CR" => "Costa Rica",
        "CI" => "Cote D'ivoire",
        "HR" => "Croatia",
        "CU" => "Cuba",
        "CY" => "Cyprus",
        "CZ" => "Czech Republic",
        "DK" => "Denmark",
        "DJ" => "Djibouti",
        "DM" => "Dominica",
        "DO" => "Dominican Republic",
        "EC" => "Ecuador",
        "EG" => "Egypt",
        "SV" => "El Salvador",
        "GQ" => "Equatorial Guinea",
        "ER" => "Eritrea",
        "EE" => "Estonia",
        "ET" => "Ethiopia",
        "FK" => "Falkland Islands (Malvinas)",
        "FO" => "Faroe Islands",
        "FJ" => "Fiji",
        "FI" => "Finland",
        "FR" => "France",
        "GF" => "French Guiana",
        "PF" => "French Polynesia",
        "TF" => "French Southern Territories",
        "GA" => "Gabon",
        "GM" => "Gambia",
        "GE" => "Georgia",
        "DE" => "Germany",
        "GH" => "Ghana",
        "GI" => "Gibraltar",
        "GR" => "Greece",
        "GL" => "Greenland",
        "GD" => "Grenada",
        "GP" => "Guadeloupe",
        "GU" => "Guam",
        "GT" => "Guatemala",
        "GG" => "Guernsey",
        "GN" => "Guinea",
        "GW" => "Guinea-bissau",
        "GY" => "Guyana",
        "HT" => "Haiti",
        "HM" => "Heard Island and Mcdonald Islands",
        "VA" => "Holy See (Vatican City State)",
        "HN" => "Honduras",
        "HK" => "Hong Kong",
        "HU" => "Hungary",
        "IS" => "Iceland",
        "IN" => "India",
        "ID" => "Indonesia",
        "IR" => "Iran, Islamic Republic of",
        "IQ" => "Iraq",
        "IE" => "Ireland",
        "IM" => "Isle of Man",
        "IL" => "Israel",
        "IT" => "Italy",
        "JM" => "Jamaica",
        "JP" => "Japan",
        "JE" => "Jersey",
        "JO" => "Jordan",
        "KZ" => "Kazakhstan",
        "KE" => "Kenya",
        "KI" => "Kiribati",
        "KP" => "Korea, Democratic People's Republic of",
        "KR" => "Korea, Republic of",
        "KW" => "Kuwait",
        "KG" => "Kyrgyzstan",
        "LA" => "Lao People's Democratic Republic",
        "LV" => "Latvia",
        "LB" => "Lebanon",
        "LS" => "Lesotho",
        "LR" => "Liberia",
        "LY" => "Libyan Arab Jamahiriya",
        "LI" => "Liechtenstein",
        "LT" => "Lithuania",
        "LU" => "Luxembourg",
        "MO" => "Macao",
        "MK" => "Macedonia, The Former Yugoslav Republic of",
        "MG" => "Madagascar",
        "MW" => "Malawi",
        "MY" => "Malaysia",
        "MV" => "Maldives",
        "ML" => "Mali",
        "MT" => "Malta",
        "MH" => "Marshall Islands",
        "MQ" => "Martinique",
        "MR" => "Mauritania",
        "MU" => "Mauritius",
        "YT" => "Mayotte",
        "MX" => "Mexico",
        "FM" => "Micronesia, Federated States of",
        "MD" => "Moldova, Republic of",
        "MC" => "Monaco",
        "MN" => "Mongolia",
        "ME" => "Montenegro",
        "MS" => "Montserrat",
        "MA" => "Morocco",
        "MZ" => "Mozambique",
        "MM" => "Myanmar",
        "NA" => "Namibia",
        "NR" => "Nauru",
        "NP" => "Nepal",
        "NL" => "Netherlands",
        "AN" => "Netherlands Antilles",
        "NC" => "New Caledonia",
        "NZ" => "New Zealand",
        "NI" => "Nicaragua",
        "NE" => "Niger",
        "NG" => "Nigeria",
        "NU" => "Niue",
        "NF" => "Norfolk Island",
        "MP" => "Northern Mariana Islands",
        "NO" => "Norway",
        "OM" => "Oman",
        "PK" => "Pakistan",
        "PW" => "Palau",
        "PS" => "Palestinian Territory, Occupied",
        "PA" => "Panama",
        "PG" => "Papua New Guinea",
        "PY" => "Paraguay",
        "PE" => "Peru",
        "PH" => "Philippines",
        "PN" => "Pitcairn",
        "PL" => "Poland",
        "PT" => "Portugal",
        "PR" => "Puerto Rico",
        "QA" => "Qatar",
        "RE" => "Reunion",
        "RO" => "Romania",
        "RU" => "Russian Federation",
        "RW" => "Rwanda",
        "SH" => "Saint Helena",
        "KN" => "Saint Kitts and Nevis",
        "LC" => "Saint Lucia",
        "PM" => "Saint Pierre and Miquelon",
        "VC" => "Saint Vincent and The Grenadines",
        "WS" => "Samoa",
        "SM" => "San Marino",
        "ST" => "Sao Tome and Principe",
        "SA" => "Saudi Arabia",
        "SN" => "Senegal",
        "RS" => "Serbia",
        "SC" => "Seychelles",
        "SL" => "Sierra Leone",
        "SG" => "Singapore",
        "SK" => "Slovakia",
        "SI" => "Slovenia",
        "SB" => "Solomon Islands",
        "SO" => "Somalia",
        "ZA" => "South Africa",
        "GS" => "South Georgia and The South Sandwich Islands",
        "ES" => "Spain",
        "LK" => "Sri Lanka",
        "SD" => "Sudan",
        "SR" => "Suriname",
        "SJ" => "Svalbard and Jan Mayen",
        "SZ" => "Swaziland",
        "SE" => "Sweden",
        "CH" => "Switzerland",
        "SY" => "Syrian Arab Republic",
        "TW" => "Taiwan, Province of China",
        "TJ" => "Tajikistan",
        "TZ" => "Tanzania, United Republic of",
        "TH" => "Thailand",
        "TL" => "Timor-leste",
        "TG" => "Togo",
        "TK" => "Tokelau",
        "TO" => "Tonga",
        "TT" => "Trinidad and Tobago",
        "TN" => "Tunisia",
        "TR" => "Turkey",
        "TM" => "Turkmenistan",
        "TC" => "Turks and Caicos Islands",
        "TV" => "Tuvalu",
        "UG" => "Uganda",
        "UA" => "Ukraine",
        "AE" => "United Arab Emirates",
        "GB" => "United Kingdom",
        "US" => "United States",
        "UM" => "United States Minor Outlying Islands",
        "UY" => "Uruguay",
        "UZ" => "Uzbekistan",
        "VU" => "Vanuatu",
        "VE" => "Venezuela",
        "VN" => "Viet Nam",
        "VG" => "Virgin Islands, British",
        "VI" => "Virgin Islands, U.S.",
        "WF" => "Wallis and Futuna",
        "EH" => "Western Sahara",
        "YE" => "Yemen",
        "ZM" => "Zambia",
        "ZW" => "Zimbabwe"
    );
    if ($all != "yes") {
        unset($array['']);
    }
    return $array;
}

function countryName($type = "")
{

    $array = array(
        "AF" => "Afghanistan",
        "AX" => "Åland Islands",
        "AL" => "Albania",
        "DZ" => "Algeria",
        "AS" => "American Samoa",
        "AD" => "Andorra",
        "AO" => "Angola",
        "AI" => "Anguilla",
        "AQ" => "Antarctica",
        "AG" => "Antigua and Barbuda",
        "AR" => "Argentina",
        "AM" => "Armenia",
        "AW" => "Aruba",
        "AU" => "Australia",
        "AT" => "Austria",
        "AZ" => "Azerbaijan",
        "BS" => "Bahamas",
        "BH" => "Bahrain",
        "BD" => "Bangladesh",
        "BB" => "Barbados",
        "BY" => "Belarus",
        "BE" => "Belgium",
        "BZ" => "Belize",
        "BJ" => "Benin",
        "BM" => "Bermuda",
        "BT" => "Bhutan",
        "BO" => "Bolivia",
        "BA" => "Bosnia and Herzegovina",
        "BW" => "Botswana",
        "BV" => "Bouvet Island",
        "BR" => "Brazil",
        "IO" => "British Indian Ocean Territory",
        "BN" => "Brunei Darussalam",
        "BG" => "Bulgaria",
        "BF" => "Burkina Faso",
        "BI" => "Burundi",
        "KH" => "Cambodia",
        "CM" => "Cameroon",
        "CA" => "Canada",
        "CV" => "Cape Verde",
        "KY" => "Cayman Islands",
        "CF" => "Central African Republic",
        "TD" => "Chad",
        "CL" => "Chile",
        "CN" => "China",
        "CX" => "Christmas Island",
        "CC" => "Cocos (Keeling) Islands",
        "CO" => "Colombia",
        "KM" => "Comoros",
        "CG" => "Congo",
        "CD" => "Congo, The Democratic Republic of The",
        "CK" => "Cook Islands",
        "CR" => "Costa Rica",
        "CI" => "Cote D'ivoire",
        "HR" => "Croatia",
        "CU" => "Cuba",
        "CY" => "Cyprus",
        "CZ" => "Czech Republic",
        "DK" => "Denmark",
        "DJ" => "Djibouti",
        "DM" => "Dominica",
        "DO" => "Dominican Republic",
        "EC" => "Ecuador",
        "EG" => "Egypt",
        "SV" => "El Salvador",
        "GQ" => "Equatorial Guinea",
        "ER" => "Eritrea",
        "EE" => "Estonia",
        "ET" => "Ethiopia",
        "FK" => "Falkland Islands (Malvinas)",
        "FO" => "Faroe Islands",
        "FJ" => "Fiji",
        "FI" => "Finland",
        "FR" => "France",
        "GF" => "French Guiana",
        "PF" => "French Polynesia",
        "TF" => "French Southern Territories",
        "GA" => "Gabon",
        "GM" => "Gambia",
        "GE" => "Georgia",
        "DE" => "Germany",
        "GH" => "Ghana",
        "GI" => "Gibraltar",
        "GR" => "Greece",
        "GL" => "Greenland",
        "GD" => "Grenada",
        "GP" => "Guadeloupe",
        "GU" => "Guam",
        "GT" => "Guatemala",
        "GG" => "Guernsey",
        "GN" => "Guinea",
        "GW" => "Guinea-bissau",
        "GY" => "Guyana",
        "HT" => "Haiti",
        "HM" => "Heard Island and Mcdonald Islands",
        "VA" => "Holy See (Vatican City State)",
        "HN" => "Honduras",
        "HK" => "Hong Kong",
        "HU" => "Hungary",
        "IS" => "Iceland",
        "IN" => "India",
        "ID" => "Indonesia",
        "IR" => "Iran, Islamic Republic of",
        "IQ" => "Iraq",
        "IE" => "Ireland",
        "IM" => "Isle of Man",
        "IL" => "Israel",
        "IT" => "Italy",
        "JM" => "Jamaica",
        "JP" => "Japan",
        "JE" => "Jersey",
        "JO" => "Jordan",
        "KZ" => "Kazakhstan",
        "KE" => "Kenya",
        "KI" => "Kiribati",
        "KP" => "Korea, Democratic People's Republic of",
        "KR" => "Korea, Republic of",
        "KW" => "Kuwait",
        "KG" => "Kyrgyzstan",
        "LA" => "Lao People's Democratic Republic",
        "LV" => "Latvia",
        "LB" => "Lebanon",
        "LS" => "Lesotho",
        "LR" => "Liberia",
        "LY" => "Libyan Arab Jamahiriya",
        "LI" => "Liechtenstein",
        "LT" => "Lithuania",
        "LU" => "Luxembourg",
        "MO" => "Macao",
        "MK" => "Macedonia, The Former Yugoslav Republic of",
        "MG" => "Madagascar",
        "MW" => "Malawi",
        "MY" => "Malaysia",
        "MV" => "Maldives",
        "ML" => "Mali",
        "MT" => "Malta",
        "MH" => "Marshall Islands",
        "MQ" => "Martinique",
        "MR" => "Mauritania",
        "MU" => "Mauritius",
        "YT" => "Mayotte",
        "MX" => "Mexico",
        "FM" => "Micronesia, Federated States of",
        "MD" => "Moldova, Republic of",
        "MC" => "Monaco",
        "MN" => "Mongolia",
        "ME" => "Montenegro",
        "MS" => "Montserrat",
        "MA" => "Morocco",
        "MZ" => "Mozambique",
        "MM" => "Myanmar",
        "NA" => "Namibia",
        "NR" => "Nauru",
        "NP" => "Nepal",
        "NL" => "Netherlands",
        "AN" => "Netherlands Antilles",
        "NC" => "New Caledonia",
        "NZ" => "New Zealand",
        "NI" => "Nicaragua",
        "NE" => "Niger",
        "NG" => "Nigeria",
        "NU" => "Niue",
        "NF" => "Norfolk Island",
        "MP" => "Northern Mariana Islands",
        "NO" => "Norway",
        "OM" => "Oman",
        "PK" => "Pakistan",
        "PW" => "Palau",
        "PS" => "Palestinian Territory, Occupied",
        "PA" => "Panama",
        "PG" => "Papua New Guinea",
        "PY" => "Paraguay",
        "PE" => "Peru",
        "PH" => "Philippines",
        "PN" => "Pitcairn",
        "PL" => "Poland",
        "PT" => "Portugal",
        "PR" => "Puerto Rico",
        "QA" => "Qatar",
        "RE" => "Reunion",
        "RO" => "Romania",
        "RU" => "Russian Federation",
        "RW" => "Rwanda",
        "SH" => "Saint Helena",
        "KN" => "Saint Kitts and Nevis",
        "LC" => "Saint Lucia",
        "PM" => "Saint Pierre and Miquelon",
        "VC" => "Saint Vincent and The Grenadines",
        "WS" => "Samoa",
        "SM" => "San Marino",
        "ST" => "Sao Tome and Principe",
        "SA" => "Saudi Arabia",
        "SN" => "Senegal",
        "RS" => "Serbia",
        "SC" => "Seychelles",
        "SL" => "Sierra Leone",
        "SG" => "Singapore",
        "SK" => "Slovakia",
        "SI" => "Slovenia",
        "SB" => "Solomon Islands",
        "SO" => "Somalia",
        "ZA" => "South Africa",
        "GS" => "South Georgia and The South Sandwich Islands",
        "ES" => "Spain",
        "LK" => "Sri Lanka",
        "SD" => "Sudan",
        "SR" => "Suriname",
        "SJ" => "Svalbard and Jan Mayen",
        "SZ" => "Swaziland",
        "SE" => "Sweden",
        "CH" => "Switzerland",
        "SY" => "Syrian Arab Republic",
        "TW" => "Taiwan, Province of China",
        "TJ" => "Tajikistan",
        "TZ" => "Tanzania, United Republic of",
        "TH" => "Thailand",
        "TL" => "Timor-leste",
        "TG" => "Togo",
        "TK" => "Tokelau",
        "TO" => "Tonga",
        "TT" => "Trinidad and Tobago",
        "TN" => "Tunisia",
        "TR" => "Turkey",
        "TM" => "Turkmenistan",
        "TC" => "Turks and Caicos Islands",
        "TV" => "Tuvalu",
        "UG" => "Uganda",
        "UA" => "Ukraine",
        "AE" => "United Arab Emirates",
        "GB" => "United Kingdom",
        "US" => "United States",
        "UM" => "United States Minor Outlying Islands",
        "UY" => "Uruguay",
        "UZ" => "Uzbekistan",
        "VU" => "Vanuatu",
        "VE" => "Venezuela",
        "VN" => "Viet Nam",
        "VG" => "Virgin Islands, British",
        "VI" => "Virgin Islands, U.S.",
        "WF" => "Wallis and Futuna",
        "EH" => "Western Sahara",
        "YE" => "Yemen",
        "ZM" => "Zambia",
        "ZW" => "Zimbabwe"
    );
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return $type;
    }
}


function filterAllValue($type = "")
{
    $array = array(
        // '' => __('None'),
        'ar' => __('Arabic'),
        'en' => __('English'),
        'all' => __('All'),
        'clients' => __('Clients'),
        'users' => __('Users'),
        'owner' => __('Owner'),
        'card' => __('Card'),
        'passport' => __('Passport'),
        'checkin' => __('Checkin'),
        'checkout' => __('Checkout'),
        'request' => __('Request'),
        'approve' => __('Approve'),
        'finish' => __('Finish'),
        'delay' => __('Delay'),
        'open' => __('Open'),
        'reject' => __('Reject'),
        'closed' => __('Closed'),
        'not_resolved' => __('Not Resolved'),
        'solved' => __('Solved'),
        'follow_up' => __('Follow Up'),
        'call' => __('Call'),
        'call_back' => __('Call Back'),
        // 'child'     => __('إبن'),
        'son' => __('إبن'),
        'daughter' => __('إبنة'),
        'wife' => __('زوجة'),
        'husband' => __('الزوج'),
        'father' => __('الاب'),
        'mother' => __('الام'),
        'grand_father' => __('الجد'),
        'grand_mother' => __('الجدو'),
        'sister' => __('اخت'),
        'brother' => __('اخ'),
        'uncle' => __('عم'),
        'aunt' => __('عمة'),
        'cousin' => __('ابن عم / ابن عمة'),
        'buyer' => __('Buyer'),
        'personal' => __('Personal'),
        'referral' => __('Referral'),
        'broker' => __('Broker'),
        'tv' => __('TV'),
        'old_data' => __('Old Data'),
        'data' => __('Data'),
        'call_drooped' => __('Call Drooped'),
        'cold_calls' => __('Cold Calls'),
        'client' => __('Client'),
        'empolyee' => __('Empolyee'),
        'email' => __('Email'),
        'meeting' => __('Meeting'),
        'sms' => __('SMS'),
        'new' => __('New'),
        'in_progress' => __('In Progress'),
        'in_active' => __('In Active'),
        'cancel' => __('Cancel'),
        'branches' => __('Branches'),
        'groups' => __('Groups'),
        'branch' => __('Branch'),
        'group' => __('Group'),
        'reasons' => __('Reason'),
        'other' => __('Other'),
        'active' => __('Active'),
        'unpaid' => __('Unpaid'),
        'paid' => __('Paid'),
        'income' => __('Income'),
        'outcome' => __('Outcome'),
        'cash' => __('Cash'),
        'bank' => __('Bank'),
        'check' => __('Check'),
        'bank_transfer' => __('Bank Transfer'),
        'paypal' => __('Paypal'),
        'visa' => __('Visa'),
        'master_card' => __('Master Card'),
        'mada' => __('Mada'),
        'apple_pay' => __('Apple Pay'),
        'google_pay' => __('Google Pay'),
        'text' => __('Text'),
        'select' => __('Select'),
        'datepicker' => __('Date'),
        'number' => __('Number'),
        'image' => __('Image'),
        'file' => __('File'),
        'qualified' => __('Qualified'),
        'fixed' => __('Fixed'),
        'percentage' => __('Percentage'),
        1 => __('Active'),
        0 => __('In Active'),
        'yes' => __('yes'),
        'no' => __('No'),
        'planned' => __('Planned'),
        'not_planned' => __('Not Planned'),
        'done' => __('Done'),
        "AF" => "Afghanistan",
        "AX" => "Åland Islands",
        "AL" => "Albania",
        "DZ" => "Algeria",
        "AS" => "American Samoa",
        "AD" => "Andorra",
        "AO" => "Angola",
        "AI" => "Anguilla",
        "AQ" => "Antarctica",
        "AG" => "Antigua and Barbuda",
        "AR" => "Argentina",
        "AM" => "Armenia",
        "AW" => "Aruba",
        "AU" => "Australia",
        "AT" => "Austria",
        "AZ" => "Azerbaijan",
        "BS" => "Bahamas",
        "BH" => "Bahrain",
        "BD" => "Bangladesh",
        "BB" => "Barbados",
        "BY" => "Belarus",
        "BE" => "Belgium",
        "BZ" => "Belize",
        "BJ" => "Benin",
        "BM" => "Bermuda",
        "BT" => "Bhutan",
        "BO" => "Bolivia",
        "BA" => "Bosnia and Herzegovina",
        "BW" => "Botswana",
        "BV" => "Bouvet Island",
        "BR" => "Brazil",
        "IO" => "British Indian Ocean Territory",
        "BN" => "Brunei Darussalam",
        "BG" => "Bulgaria",
        "BF" => "Burkina Faso",
        "BI" => "Burundi",
        "KH" => "Cambodia",
        "CM" => "Cameroon",
        "CA" => "Canada",
        "CV" => "Cape Verde",
        "KY" => "Cayman Islands",
        "CF" => "Central African Republic",
        "TD" => "Chad",
        "CL" => "Chile",
        "CN" => "China",
        "CX" => "Christmas Island",
        "CC" => "Cocos (Keeling) Islands",
        "CO" => "Colombia",
        "KM" => "Comoros",
        "CG" => "Congo",
        "CD" => "Congo, The Democratic Republic of The",
        "CK" => "Cook Islands",
        "CR" => "Costa Rica",
        "CI" => "Cote D'ivoire",
        "HR" => "Croatia",
        "CU" => "Cuba",
        "CY" => "Cyprus",
        "CZ" => "Czech Republic",
        "DK" => "Denmark",
        "DJ" => "Djibouti",
        "DM" => "Dominica",
        "DO" => "Dominican Republic",
        "EC" => "Ecuador",
        "EG" => "Egypt",
        "SV" => "El Salvador",
        "GQ" => "Equatorial Guinea",
        "ER" => "Eritrea",
        "EE" => "Estonia",
        "ET" => "Ethiopia",
        "FK" => "Falkland Islands (Malvinas)",
        "FO" => "Faroe Islands",
        "FJ" => "Fiji",
        "FI" => "Finland",
        "FR" => "France",
        "GF" => "French Guiana",
        "PF" => "French Polynesia",
        "TF" => "French Southern Territories",
        "GA" => "Gabon",
        "GM" => "Gambia",
        "GE" => "Georgia",
        "DE" => "Germany",
        "GH" => "Ghana",
        "GI" => "Gibraltar",
        "GR" => "Greece",
        "GL" => "Greenland",
        "GD" => "Grenada",
        "GP" => "Guadeloupe",
        "GU" => "Guam",
        "GT" => "Guatemala",
        "GG" => "Guernsey",
        "GN" => "Guinea",
        "GW" => "Guinea-bissau",
        "GY" => "Guyana",
        "HT" => "Haiti",
        "HM" => "Heard Island and Mcdonald Islands",
        "VA" => "Holy See (Vatican City State)",
        "HN" => "Honduras",
        "HK" => "Hong Kong",
        "HU" => "Hungary",
        "IS" => "Iceland",
        "IN" => "India",
        "ID" => "Indonesia",
        "IR" => "Iran, Islamic Republic of",
        "IQ" => "Iraq",
        "IE" => "Ireland",
        "IM" => "Isle of Man",
        "IL" => "Israel",
        "IT" => "Italy",
        "JM" => "Jamaica",
        "JP" => "Japan",
        "JE" => "Jersey",
        "JO" => "Jordan",
        "KZ" => "Kazakhstan",
        "KE" => "Kenya",
        "KI" => "Kiribati",
        "KP" => "Korea, Democratic People's Republic of",
        "KR" => "Korea, Republic of",
        "KW" => "Kuwait",
        "KG" => "Kyrgyzstan",
        "LA" => "Lao People's Democratic Republic",
        "LV" => "Latvia",
        "LB" => "Lebanon",
        "LS" => "Lesotho",
        "LR" => "Liberia",
        "LY" => "Libyan Arab Jamahiriya",
        "LI" => "Liechtenstein",
        "LT" => "Lithuania",
        "LU" => "Luxembourg",
        "MO" => "Macao",
        "MK" => "Macedonia, The Former Yugoslav Republic of",
        "MG" => "Madagascar",
        "MW" => "Malawi",
        "MY" => "Malaysia",
        "MV" => "Maldives",
        "ML" => "Mali",
        "MT" => "Malta",
        "MH" => "Marshall Islands",
        "MQ" => "Martinique",
        "MR" => "Mauritania",
        "MU" => "Mauritius",
        "YT" => "Mayotte",
        "MX" => "Mexico",
        "FM" => "Micronesia, Federated States of",
        "MD" => "Moldova, Republic of",
        "MC" => "Monaco",
        "MN" => "Mongolia",
        "ME" => "Montenegro",
        "MS" => "Montserrat",
        "MA" => "Morocco",
        "MZ" => "Mozambique",
        "MM" => "Myanmar",
        "NA" => "Namibia",
        "NR" => "Nauru",
        "NP" => "Nepal",
        "NL" => "Netherlands",
        "AN" => "Netherlands Antilles",
        "NC" => "New Caledonia",
        "NZ" => "New Zealand",
        "NI" => "Nicaragua",
        "NE" => "Niger",
        "NG" => "Nigeria",
        "NU" => "Niue",
        "NF" => "Norfolk Island",
        "MP" => "Northern Mariana Islands",
        "NO" => "Norway",
        "OM" => "Oman",
        "PK" => "Pakistan",
        "PW" => "Palau",
        "PS" => "Palestinian Territory, Occupied",
        "PA" => "Panama",
        "PG" => "Papua New Guinea",
        "PY" => "Paraguay",
        "PE" => "Peru",
        "PH" => "Philippines",
        "PN" => "Pitcairn",
        "PL" => "Poland",
        "PT" => "Portugal",
        "PR" => "Puerto Rico",
        "QA" => "Qatar",
        "RE" => "Reunion",
        "RO" => "Romania",
        "RU" => "Russian Federation",
        "RW" => "Rwanda",
        "SH" => "Saint Helena",
        "KN" => "Saint Kitts and Nevis",
        "LC" => "Saint Lucia",
        "PM" => "Saint Pierre and Miquelon",
        "VC" => "Saint Vincent and The Grenadines",
        "WS" => "Samoa",
        "SM" => "San Marino",
        "ST" => "Sao Tome and Principe",
        "SA" => "Saudi Arabia",
        "SN" => "Senegal",
        "RS" => "Serbia",
        "SC" => "Seychelles",
        "SL" => "Sierra Leone",
        "SG" => "Singapore",
        "SK" => "Slovakia",
        "SI" => "Slovenia",
        "SB" => "Solomon Islands",
        "SO" => "Somalia",
        "ZA" => "South Africa",
        "GS" => "South Georgia and The South Sandwich Islands",
        "ES" => "Spain",
        "LK" => "Sri Lanka",
        "SD" => "Sudan",
        "SR" => "Suriname",
        "SJ" => "Svalbard and Jan Mayen",
        "SZ" => "Swaziland",
        "SE" => "Sweden",
        "CH" => "Switzerland",
        "SY" => "Syrian Arab Republic",
        "TW" => "Taiwan, Province of China",
        "TJ" => "Tajikistan",
        "TZ" => "Tanzania, United Republic of",
        "TH" => "Thailand",
        "TL" => "Timor-leste",
        "TG" => "Togo",
        "TK" => "Tokelau",
        "TO" => "Tonga",
        "TT" => "Trinidad and Tobago",
        "TN" => "Tunisia",
        "TR" => "Turkey",
        "TM" => "Turkmenistan",
        "TC" => "Turks and Caicos Islands",
        "TV" => "Tuvalu",
        "UG" => "Uganda",
        "UA" => "Ukraine",
        "AE" => "United Arab Emirates",
        "GB" => "United Kingdom",
        "US" => "United States",
        "UM" => "United States Minor Outlying Islands",
        "UY" => "Uruguay",
        "UZ" => "Uzbekistan",
        "VU" => "Vanuatu",
        "VE" => "Venezuela",
        "VN" => "Viet Nam",
        "VG" => "Virgin Islands, British",
        "VI" => "Virgin Islands, U.S.",
        "WF" => "Wallis and Futuna",
        "EH" => "Western Sahara",
        "YE" => "Yemen",
        "ZM" => "Zambia",
        "ZW" => "Zimbabwe",
        'PATCH' => __("Update"),
        'PUT' => __("Update"),
        'POST' => __("Create"),
        'DELETE' => __("Delete"),
        'GET' => __("Show"),
        'admin' => __('Admin'),
        'manger' => __('Manger'),
        'view' => __('View'),
        'create' => __('Create'),
        'edit' => __('Edit'),
        'show' => __('Show'),
        'delete' => __('Delete')
    );
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return $type;
    }
}

function filterAllType($all = "yes")
{
    $array = array(
        'ar' => __('Arabic'),
        'en' => __('English'),
        'all' => __('All'),
        'clients' => __('Clients'),
        'users' => __('Users'),
        'owner' => __('Owner'),
        'card' => __('Card'),
        'passport' => __('Passport'),
        'checkin' => __('Checkin'),
        'checkout' => __('Checkout'),
        'request' => __('Request'),
        'approve' => __('Approve'),
        'finish' => __('Finish'),
        'delay' => __('Delay'),
        'open' => __('Open'),
        'reject' => __('Reject'),
        'closed' => __('Closed'),
        'not_resolved' => __('Not Resolved'),
        'solved' => __('Solved'),
        'follow_up' => __('Follow Up'),
        'call' => __('Call'),
        'call_back' => __('Call Back'),
        'child' => __('Child'),
        'son' => __('إبن'),
        'daughter' => __('إبنة'),
        'wife' => __('زوجة'),
        'husband' => __('الزوج'),
        'father' => __('الاب'),
        'mother' => __('الام'),
        'grand_father' => __('الجد'),
        'grand_mother' => __('الجدو'),
        'sister' => __('اخت'),
        'brother' => __('اخ'),
        'uncle' => __('عم / خال'),
        'aunt' => __('عمة / خالة'),
        'cousin' => __('ابن / ابنة عمة / خالة'),
        'buyer' => __('Buyer'),
        'personal' => __('Personal'),
        'referral' => __('Referral'),
        'broker' => __('Broker'),
        'tv' => __('TV'),
        'old_data' => __('Old Data'),
        'data' => __('Data'),
        'call_drooped' => __('Call Drooped'),
        'cold_calls' => __('Cold Calls'),
        'client' => __('Client'),
        'empolyee' => __('Empolyee'),
        'email' => __('Email'),
        'meeting' => __('Meeting'),
        'sms' => __('SMS'),
        'new' => __('New'),
        'in_progress' => __('In Progress'),
        'in_active' => __('In Active'),
        'cancel' => __('Cancel'),
        'branches' => __('Branches'),
        'groups' => __('Groups'),
        'branch' => __('Branch'),
        'group' => __('Group'),
        'reasons' => __('Reason'),
        'other' => __('Other'),
        'active' => __('Active'),
        'unpaid' => __('Unpaid'),
        'paid' => __('Paid'),
        'income' => __('Income'),
        'outcome' => __('Outcome'),
        'cash' => __('Cash'),
        'bank' => __('Bank'),
        'check' => __('Check'),
        'bank_transfer' => __('Bank Transfer'),
        'paypal' => __('Paypal'),
        'visa' => __('Visa'),
        'master_card' => __('Master Card'),
        'mada' => __('Mada'),
        'apple_pay' => __('Apple Pay'),
        'google_pay' => __('Google Pay'),
        'text' => __('Text'),
        'select' => __('Select'),
        'datepicker' => __('Date'),
        'number' => __('Number'),
        'image' => __('Image'),
        'file' => __('File'),
        'qualified' => __('Qualified'),
        'fixed' => __('Fixed'),
        'percentage' => __('Percentage'),
        1 => __('Active'),
        0 => __('In Active'),
        'yes' => __('yes'),
        'no' => __('No'),
        'planned' => __('Planned'),
        'not_planned' => __('Not Planned'),
        'done' => __('Done'),
    );
    if ($all != "yes") {
        unset($array['']);
    }
    return $array;
}

function getRouteName($route_type = "")
{
    $array = array(
        'admin' => 'admin',
        'client' => 'client',
        'employee' => 'employee',
        'facilty' => 'facilty',
    );
    if (isset($array[$route_type])) {
        return $array[$route_type];
    } else {
        return "admin";
    }
}


function nationalName($type = "")
{
    $array = array(
        '' => __('None'),
        'card' => __('Card'),
        'passport' => __('Passport'),
    );

    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return $type;
    }
}

function logActionType($all = "yes")
{
    $array = array(
        '' => __('None'),
        'Update' => __("Update"),
        'Create' => __("Create"),
        'Delete' => __("Delete"),
        'Show' => __("Show"),
    );
    if ($all != "yes") {
        unset($array['']);
    }
    return $array;
}

function logActionName($type)
{
    $array = array(
        'Create' => ("POST"),
        'Show' => ("GET"),
        'Update' => ("PUT"),
        'Delete' => ("DELETE"),
    );
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return $type;
    }
}

function logAction($type)
{
    $array = array(
        '' => __('None'),
        'PATCH' => __("Update"),
        'PUT' => __("Update"),
        'POST' => __("Create"),
        'DELETE' => __("Delete"),
        'GET' => __("Show"),
    );
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return $type;
    }
}

function notificationSystemRoute($type = "")
{
    $array = [
        'orders' => "orders",
        'services' => "services",
    ];
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return "";
    }
}

function orderName($type = "")
{
    $array = array(
        "request" => __('Request'),
        "approve" => __('Approve'),
        "pending" => __('Pending'),
        "finish" => __('Finish'),
        "cancel" => __('Cancel'),
    );
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return "";
    }
}

function pageName($type = "")
{
    $array = array(
        'all' => __('All'),
        'home' => __('Home'),
        'about' => __('About'),
        'contact' => __('Contact'),
        'contact_form' => __('Contact Form'),
        'terms' => __('Terms'),
        'privacy' => __('Privacy'),
        'faq' => __('Faq'),
        'branch' => __('Branch'),
        'profile' => __('Profile'),
        'team' => __('Team'),
        'mission' => __('Mission'),
        'vision' => __('Vision'),
        'value' => __('Value'),
        'address' => __('Address'),
        'work_time' => __('Work Time'),
        'appointment' => __("Appointment"),
        'feature' => __("Feature"),
        'gift' => __("Gift"),
        'goal' => __("Goal"),
        'testimonial' => __("Testimonial"),
        'service' => __("Service"),
        'post' => __("Post"),
        'category' => __("Category"),
        'copyright' => __('Copy Right'),
        'social' => __('Social'),
        'slider' => __('Slider'),
        'header' => __('Header'),
        'footer' => __('footer'),
    );
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return "";
    }
}

function hourName($type)
{
    $array = array(
        '00:00:00' => "00:00",
        '00:30:00' => "00:30",
        '01:00:00' => "01:00",
        '01:30:00' => "01:30",
        '02:00:00' => "02:00",
        '02:30:00' => "02:30",
        '03:00:00' => "03:00",
        '03:30:00' => "03:30",
        '04:00:00' => "04:00",
        '04:30:00' => "04:30",
        '05:00:00' => "05:00",
        '05:30:00' => "05:30",
        '06:00:00' => "06:00",
        '06:30:00' => "06:30",
        '07:00:00' => "07:00",
        '07:30:00' => "07:30",
        '08:00:00' => "08:00",
        '08:30:00' => "08:30",
        '09:00:00' => "09:00",
        '09:30:00' => "09:30",
        '10:00:00' => "10:00",
        '10:30:00' => "10:30",
        '11:00:00' => "12:00",
        '11:30:00' => "12:30",
        '12:00:00' => "12:00",
        '12:30:00' => "12:30",
        '13:00:00' => "13:00",
        '13:30:00' => "13:30",
        '14:00:00' => "14:00",
        '14:30:00' => "14:30",
        '15:00:00' => "15:00",
        '15:30:00' => "15:30",
        '16:00:00' => "16:00",
        '16:30:00' => "16:30",
        '17:00:00' => "17:00",
        '17:30:00' => "17:30",
        '18:00:00' => "18:00",
        '18:30:00' => "18:30",
        '19:00:00' => "19:00",
        '19:30:00' => "19:30",
        '20:00:00' => "20:00",
        '20:30:00' => "20:30",
        '21:00:00' => "21:00",
        '21:30:00' => "21:30",
        '22:00:00' => "22:00",
        '22:30:00' => "22:30",
        '23:00:00' => "23:00",
        '23:30:00' => "23:30",
    );
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return "";
    }
}

function supportName($type)
{
    $array = array(
        '' => __('None'),
        'username' => __('Change User Name'),
        'password' => __('Change Password'),
    );
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return "";
    }
}

function checkPermView($route_admin)
{
    $route_name = false;
    $permission = Permission::whereRaw("FIND_IN_SET('$route_admin',name)")->first();
    if ($permission) {
        if (auth('web')->user()->isAbleTo($permission->name)) {
            $route_name = true;
        }
    }
    return $route_name;
}

function relativeType($all = "yes")
{
    $types = array(
        '' => __('إختر صلة القرابة'),
        // 'child'     => __('Child'),
        'son' => __('إبن'),
        'daughter' => __('إبنة'),
        'wife' => __('زوجة'),
        'husband' => __('الزوج'),
        'father' => __('الاب'),
        'mother' => __('الام'),
        'grand_father' => __('الجد'),
        'grand_mother' => __('الجدة'),
        'sister' => __('اخت'),
        'brother' => __('اخ'),
        'uncle' => __('عم / خال'),
        'aunt' => __('عمة / خالة'),
        'cousin' => __('ابن / ابنة عمة / خالة'),
        'other' => __('اخري'),
    );
    if ($all != "yes") {
        unset($types['']);
    }

    return $types;
}

function moneyName($type)
{
    $array = array(
        'day' => __('يوم'),
        'week' => __('اسبوع'),
        'month' => __('شهر'),
        'year' => __('سنة'),
        'project' => __('مشروع'),
        'task' => __('مهمة'),
    );
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return "";
    }
}

function currencyName($type)
{
    $array = array(
        'EGP' => __('جنيه مصري'),
        'SAR' => __('ريال سعودي'),
        'USD' => __('دولار امريكي'),
    );
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return "";
    }
}

function vacationName($type)
{
    $array = array(
        '' => __('None'),
        'work' => __('Work'),
        'sick' => __('Sick'),
        'entertainment' => __('Entertainment'),
    );
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return "";
    }
}

function vacationNameCheckbox($type)
{
    $array = array(
        '0' => __('خصم من الرصيد'),
        '1' => __('خصم يوم'),
        '1.5' => __('خصم يوم ونصف '),
        '2' => __('خصم يومين'),
    );
    if (isset($array[$type])) {
        return $array[$type];
    } else {
        return "";
    }
}


function getAllTimeZones()
{
    return array(
        '(UTC-11:00) Midway Island' => 'Pacific/Midway',
        '(UTC-11:00) Samoa' => 'Pacific/Samoa',
        '(UTC-10:00) Hawaii' => 'Pacific/Honolulu',
        '(UTC-09:00) Alaska' => 'US/Alaska',
        '(UTC-08:00) Pacific Time (US &amp; Canada)' => 'America/Los_Angeles',
        '(UTC-08:00) Tijuana' => 'America/Tijuana',
        '(UTC-07:00) Arizona' => 'US/Arizona',
        '(UTC-07:00) Chihuahua' => 'America/Chihuahua',
        '(UTC-07:00) La Paz' => 'America/Chihuahua',
        '(UTC-07:00) Mazatlan' => 'America/Mazatlan',
        '(UTC-07:00) Mountain Time (US &amp; Canada)' => 'US/Mountain',
        '(UTC-06:00) Central America' => 'America/Managua',
        '(UTC-06:00) Central Time (US &amp; Canada)' => 'US/Central',
        '(UTC-06:00) Guadalajara' => 'America/Mexico_City',
        '(UTC-06:00) Mexico City' => 'America/Mexico_City',
        '(UTC-06:00) Monterrey' => 'America/Monterrey',
        '(UTC-06:00) Saskatchewan' => 'Canada/Saskatchewan',
        '(UTC-05:00) Bogota' => 'America/Bogota',
        '(UTC-05:00) Eastern Time (US &amp; Canada)' => 'US/Eastern',
        '(UTC-05:00) Indiana (East)' => 'US/East-Indiana',
        '(UTC-05:00) Lima' => 'America/Lima',
        '(UTC-05:00) Quito' => 'America/Bogota',
        '(UTC-04:00) Atlantic Time (Canada)' => 'Canada/Atlantic',
        '(UTC-04:30) Caracas' => 'America/Caracas',
        '(UTC-04:00) La Paz' => 'America/La_Paz',
        '(UTC-04:00) Santiago' => 'America/Santiago',
        '(UTC-03:30) Newfoundland' => 'Canada/Newfoundland',
        '(UTC-03:00) Brasilia' => 'America/Sao_Paulo',
        '(UTC-03:00) Buenos Aires' => 'America/Argentina/Buenos_Aires',
        '(UTC-03:00) Georgetown' => 'America/Argentina/Buenos_Aires',
        '(UTC-03:00) Greenland' => 'America/Godthab',
        '(UTC-02:00) Mid-Atlantic' => 'America/Noronha',
        '(UTC-01:00) Azores' => 'Atlantic/Azores',
        '(UTC-01:00) Cape Verde Is.' => 'Atlantic/Cape_Verde',
        '(UTC+00:00) Casablanca' => 'Africa/Casablanca',
        '(UTC+00:00) Edinburgh' => 'Europe/London',
        '(UTC+00:00) Greenwich Mean Time : Dublin' => 'Etc/Greenwich',
        '(UTC+00:00) Lisbon' => 'Europe/Lisbon',
        '(UTC+00:00) London' => 'Europe/London',
        '(UTC+00:00) Monrovia' => 'Africa/Monrovia',
        '(UTC+00:00) UTC' => 'UTC',
        '(UTC+01:00) Amsterdam' => 'Europe/Amsterdam',
        '(UTC+01:00) Belgrade' => 'Europe/Belgrade',
        '(UTC+01:00) Berlin' => 'Europe/Berlin',
        '(UTC+01:00) Bern' => 'Europe/Berlin',
        '(UTC+01:00) Bratislava' => 'Europe/Bratislava',
        '(UTC+01:00) Brussels' => 'Europe/Brussels',
        '(UTC+01:00) Budapest' => 'Europe/Budapest',
        '(UTC+01:00) Copenhagen' => 'Europe/Copenhagen',
        '(UTC+01:00) Ljubljana' => 'Europe/Ljubljana',
        '(UTC+01:00) Madrid' => 'Europe/Madrid',
        '(UTC+01:00) Paris' => 'Europe/Paris',
        '(UTC+01:00) Prague' => 'Europe/Prague',
        '(UTC+01:00) Rome' => 'Europe/Rome',
        '(UTC+01:00) Sarajevo' => 'Europe/Sarajevo',
        '(UTC+01:00) Skopje' => 'Europe/Skopje',
        '(UTC+01:00) Stockholm' => 'Europe/Stockholm',
        '(UTC+01:00) Vienna' => 'Europe/Vienna',
        '(UTC+01:00) Warsaw' => 'Europe/Warsaw',
        '(UTC+01:00) West Central Africa' => 'Africa/Lagos',
        '(UTC+01:00) Zagreb' => 'Europe/Zagreb',
        '(UTC+02:00) Athens' => 'Europe/Athens',
        '(UTC+02:00) Bucharest' => 'Europe/Bucharest',
        '(UTC+02:00) Cairo' => 'Africa/Cairo',
        '(UTC+02:00) Harare' => 'Africa/Harare',
        '(UTC+02:00) Helsinki' => 'Europe/Helsinki',
        '(UTC+02:00) Istanbul' => 'Europe/Istanbul',
        '(UTC+02:00) Jerusalem' => 'Asia/Jerusalem',
        '(UTC+02:00) Kyiv' => 'Europe/Helsinki',
        '(UTC+02:00) Pretoria' => 'Africa/Johannesburg',
        '(UTC+02:00) Riga' => 'Europe/Riga',
        '(UTC+02:00) Sofia' => 'Europe/Sofia',
        '(UTC+02:00) Tallinn' => 'Europe/Tallinn',
        '(UTC+02:00) Vilnius' => 'Europe/Vilnius',
        '(UTC+03:00) Baghdad' => 'Asia/Baghdad',
        '(UTC+03:00) Kuwait' => 'Asia/Kuwait',
        '(UTC+03:00) Minsk' => 'Europe/Minsk',
        '(UTC+03:00) Nairobi' => 'Africa/Nairobi',
        '(UTC+03:00) Riyadh' => 'Asia/Riyadh',
        '(UTC+03:00) Volgograd' => 'Europe/Volgograd',
        '(UTC+03:30) Tehran' => 'Asia/Tehran',
        '(UTC+04:00) Abu Dhabi' => 'Asia/Muscat',
        '(UTC+04:00) Baku' => 'Asia/Baku',
        '(UTC+04:00) Moscow' => 'Europe/Moscow',
        '(UTC+04:00) Muscat' => 'Asia/Muscat',
        '(UTC+04:00) St. Petersburg' => 'Europe/Moscow',
        '(UTC+04:00) Tbilisi' => 'Asia/Tbilisi',
        '(UTC+04:00) Yerevan' => 'Asia/Yerevan',
        '(UTC+04:30) Kabul' => 'Asia/Kabul',
        '(UTC+05:00) Islamabad' => 'Asia/Karachi',
        '(UTC+05:00) Karachi' => 'Asia/Karachi',
        '(UTC+05:00) Tashkent' => 'Asia/Tashkent',
        '(UTC+05:30) Chennai' => 'Asia/Calcutta',
        '(UTC+05:30) Kolkata' => 'Asia/Kolkata',
        '(UTC+05:30) Mumbai' => 'Asia/Calcutta',
        '(UTC+05:30) New Delhi' => 'Asia/Calcutta',
        '(UTC+05:30) Sri Jayawardenepura' => 'Asia/Calcutta',
        '(UTC+05:45) Kathmandu' => 'Asia/Katmandu',
        '(UTC+06:00) Almaty' => 'Asia/Almaty',
        '(UTC+06:00) Astana' => 'Asia/Dhaka',
        '(UTC+06:00) Dhaka' => 'Asia/Dhaka',
        '(UTC+06:00) Ekaterinburg' => 'Asia/Yekaterinburg',
        '(UTC+06:30) Rangoon' => 'Asia/Rangoon',
        '(UTC+07:00) Bangkok' => 'Asia/Bangkok',
        '(UTC+07:00) Hanoi' => 'Asia/Bangkok',
        '(UTC+07:00) Jakarta' => 'Asia/Jakarta',
        '(UTC+07:00) Novosibirsk' => 'Asia/Novosibirsk',
        '(UTC+08:00) Beijing' => 'Asia/Hong_Kong',
        '(UTC+08:00) Chongqing' => 'Asia/Chongqing',
        '(UTC+08:00) Hong Kong' => 'Asia/Hong_Kong',
        '(UTC+08:00) Krasnoyarsk' => 'Asia/Krasnoyarsk',
        '(UTC+08:00) Kuala Lumpur' => 'Asia/Kuala_Lumpur',
        '(UTC+08:00) Perth' => 'Australia/Perth',
        '(UTC+08:00) Singapore' => 'Asia/Singapore',
        '(UTC+08:00) Taipei' => 'Asia/Taipei',
        '(UTC+08:00) Ulaan Bataar' => 'Asia/Ulan_Bator',
        '(UTC+08:00) Urumqi' => 'Asia/Urumqi',
        '(UTC+09:00) Irkutsk' => 'Asia/Irkutsk',
        '(UTC+09:00) Osaka' => 'Asia/Tokyo',
        '(UTC+09:00) Sapporo' => 'Asia/Tokyo',
        '(UTC+09:00) Seoul' => 'Asia/Seoul',
        '(UTC+09:00) Tokyo' => 'Asia/Tokyo',
        '(UTC+09:30) Adelaide' => 'Australia/Adelaide',
        '(UTC+09:30) Darwin' => 'Australia/Darwin',
        '(UTC+10:00) Brisbane' => 'Australia/Brisbane',
        '(UTC+10:00) Canberra' => 'Australia/Canberra',
        '(UTC+10:00) Guam' => 'Pacific/Guam',
        '(UTC+10:00) Hobart' => 'Australia/Hobart',
        '(UTC+10:00) Melbourne' => 'Australia/Melbourne',
        '(UTC+10:00) Port Moresby' => 'Pacific/Port_Moresby',
        '(UTC+10:00) Sydney' => 'Australia/Sydney',
        '(UTC+10:00) Yakutsk' => 'Asia/Yakutsk',
        '(UTC+11:00) Vladivostok' => 'Asia/Vladivostok',
        '(UTC+12:00) Auckland' => 'Pacific/Auckland',
        '(UTC+12:00) Fiji' => 'Pacific/Fiji',
        '(UTC+12:00) International Date Line West' => 'Pacific/Kwajalein',
        '(UTC+12:00) Kamchatka' => 'Asia/Kamchatka',
        '(UTC+12:00) Magadan' => 'Asia/Magadan',
        '(UTC+12:00) Marshall Is.' => 'Pacific/Fiji',
        '(UTC+12:00) New Caledonia' => 'Asia/Magadan',
        '(UTC+12:00) Solomon Is.' => 'Asia/Magadan',
        '(UTC+12:00) Wellington' => 'Pacific/Auckland',
        '(UTC+13:00) Nuku\'alofa' => 'Pacific/Tongatapu');
}

function uploadFile($file, $modelName, $model_id, $column, $delete_old = false, $sizes = null, $name = null)
{
    if ($modelName == 'messages') {
        $path = 'uploads/users/' . strtolower($modelName) . '/' . auth()->id() . '/' . strtolower($column) . '/';
    } else {
        $path = 'uploads/' . strtolower($modelName) . '/' . $model_id . '/' . strtolower($column) . '/';
    }
    if ($delete_old) {
        if (File::isDirectory('storage/' . $path)) {
            File::deleteDirectory('storage/' . $path);
        }
    }
    $extension = $file->extension();
    $fileSize = $file->getSize();
    $originalName = $name ?? $file->getClientOriginalName();
    $filename = $column . '_' . time() . rand(111, 999) . '.' . $extension;
    if (auth()->check()) {
        $id = auth()->id();
    } else {
        $id = $model_id;
    }
    try {

        $file->storeAs('public/' . $path, $filename);
        if (!empty($sizes)) {
            $filePath = 'storage/' . $path;
            if (!is_dir($filePath)) {
                if (!mkdir($filePath, 0777, true) && !is_dir($filePath)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $filePath));
                }
            }
            foreach ($sizes as $size) {
                $img = ResizeImage::make($file->getRealPath());
                $img->resize($size['width'], $size['height'], function ($constraint) {
                    $constraint->aspectRatio();
                });
                $img->save($filePath . $column . '_' . $size['name'] . '.' . $extension);
                \App\Models\Attachment::create(['user_id' => $id, 'attachable_id' => $model_id,
                    'attachable_type' => $modelName, 'path' => $path . $column . '_' . $size['name'] . '.' . $extension, 'size' => $fileSize, 'original_name' => $originalName, 'extension' => $extension, 'type' => $column . '_' . $size['name']]);
            }
        }
    } catch (Exception $e) {
        return $e->getMessage();
    }
    \App\Models\Attachment::create(['user_id' => $id, 'attachable_id' => $model_id, 'attachable_type' => $modelName, 'path' => $path . $filename, 'size' => $fileSize, 'original_name' => $originalName, 'extension' => $extension, 'type' => $column]);
    $file = null;
    return $path . $filename;
}

function testuploadFile($file, $modelName, $model_id, $column, $delete_old = false, $sizes = null, $name = null)
{
    if ($modelName == 'messages') {
        $path = 'uploads/users/' . strtolower($modelName) . '/' . auth()->id() . '/' . strtolower($column) . '/';
    } else {
        $path = 'uploads/' . strtolower($modelName) . '/' . $model_id . '/' . strtolower($column) . '/';
    }
    if ($delete_old) {
        if (File::isDirectory('storage/' . $path)) {
            File::deleteDirectory('storage/' . $path);
        }
    }
    $extension = $file->extension();
    $fileSize = $file->getSize();
    $originalName = $name ?? $file->getClientOriginalName();
    $filename = $column . '_' . time() . rand(111, 999) . '.' . $extension;
    if (auth()->check()) {
        $id = auth()->id();
    } else {
        $id = $model_id;
    }
    try {
        $file->storeAs('public/' . $path, $filename);
        \App\Models\Attachment::create(['user_id' => $id, 'attachable_id' => $model_id, 'attachable_type' => $modelName, 'path' => $path . $filename, 'size' => $fileSize, 'original_name' => $originalName, 'extension' => $extension, 'type' => $column]);
        if (!empty($sizes)) {
            $filePath = $path . 'conversions/';
            if (!file_exists($filePath)) {
                mkdir(public_path('storage/' . $filePath), 777, true);
            }
            foreach ($sizes as $size) {
                $img = ResizeImage::make($file)->resize($size['width'], $size['height']);
                $img->save('storage/' . $filePath . 'conversions/' . $column . '_' . $size['name'] . '.' . $extension, 60);
//                ResizeImage::make($file)
//                    ->resize($size['width'], $size['height'])
//                    ->insert('storage/' . $filePath . $column . '_' . $size['name'] . '.' . $extension);
                \App\Models\Attachment::create(['user_id' => $id, 'attachable_id' => $model_id,
                    'attachable_type' => $modelName, 'path' => 'storage/' . $filePath . 'conversions/' . $column . '_' . $size['name'] . '.' . $extension, 'size' => $fileSize, 'original_name' => $originalName, 'extension' => $extension, 'type' => $column]);
            }
        }
    } catch (Exception $e) {
//        return $path . $filename;
        return $e->getMessage();
    }
    $file = null;
    return $path . $filename;
}

function size($bytes)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max(intval($bytes), 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= (1 << (10 * $pow));

    return round($bytes, 2) . ' ' . $units[$pow];
}

function distanceBetweenLatAndLong($lat1, $lon1, $lat2, $lon2, $unit="K")
{
    if (($lat1 == $lat2) && ($lon1 == $lon2)) {
        return 0;
    }
    else {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "K") {
            return ($miles * 1.609344);
        } else if ($unit == "N") {
            return ($miles * 0.8684);
        } else {
            return $miles;
        }
    }
}
function getCountryStateAndCityByLatAndLong($lat, $long)
{
    $countries = \Modules\Country\Models\Country::get();
    $states = \Modules\Country\Models\State::get();
    $cities = \Modules\Country\Models\City::get();

    $lowestDistance = PHP_FLOAT_MAX;
    $closestLocation = [
        'country' => null,
        'state' => null,
        'city' => null,
        'distance' => null
    ];

    foreach ($cities as $city) {
        if ($city->latitude && $city->longitude) {
            $distance = distanceBetweenLatAndLong($lat, $long, $city->latitude, $city->longitude);
            if ($distance < $lowestDistance) {
                $lowestDistance = $distance;
                $state = $states->where('id', $city->state_id)->first();
                $country = $countries->where('id', $state ? $state->country_id : null)->first();

                $closestLocation = [
                    'country' => $country,
                    'state' => $state ,
                    'city' => $city,
                    'distance' => $distance
                ];
            }
        }
    }

    return $closestLocation;
}


function ip()
{
    $whip = new Whip();
    $ip = $whip->getValidIpAddress();
    if ($ip === false) {
        $ip = Request::header('Cf-Connecting-Ip');
        if (is_null($ip)) {
            $ip = Request::ip();
        }
    }

    return $ip;
}

function country($ip)
{
    $position = Location::get($ip);

    return $position ? $position->countryCode : null;
}


function timezone($ip)
{
    $position = Location::get($ip);

    return $position ? $position->timezone : '';
}


function havePermissionTo($permission)
{
    $permissions = [];
    $user = auth()->user();

    $cookie = Cookie::get('permissions');
    if (!empty($cookie)) {
        $permissions = json_decode($cookie);
        if (in_array($permission, $permissions)) {
            return true;
        } else {
            return false;
        }
    } else {
        foreach ($user->roles as $role) {

            $permissions = array_merge($permissions, $role->permissions->pluck('name')->toArray());
        }

        if (!empty($user->permissions)) {
            $permissions = array_merge($permissions, $user->permissions->pluck('name')->toArray());
        }

        Cookie::make('permissions', json_encode($permissions));
        if (in_array($permission, $permissions)) {
            return true;
        } else {
            return false;
        }
    }
}



function distanceBetweenTwoLatLong($lat1, $lon1, $lat2, $lon2, $unit="M")
{
    if (($lat1 == $lat2) && ($lon1 == $lon2)) {
        return 0;
    } else {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "K") {
            return ($miles * 1.609344);
        }else if ($unit == "M") {
            return ($miles * 1609.34);
        }
        else if ($unit == "N") {
            return ($miles * 0.8684);
        } else {
            return $miles;
        }
    }
}


function sendOtpSms($phoneNumber, $code, $sender = 'Vision Dim')
{
    // date time format  YYYY-MM-DD HH:MM:SS
    $smsApiToken = config('app.sms_api_token');
    $smsUsername = config('app.sms_username');
//    'https://www.mora-sa.com/api/v1/sendsms?api_key=ac4aaf643e969c84b6ce9ad27254ce8fde74b44c&username=saadmashal&message=Your%20Verification%20Code%20is%20523684&sender=Vision%20Dim&numbers=966538500542&response=text'
    $smsProviderUrl = 'https://www.mora-sa.com/api/v1/sendsms?api_key=ac4aaf643e969c84b6ce9ad27254ce8fde74b44c&username=saadmashal';
//&message=Your%20Verification%20Code%20is%20' . $code . '&sender=Vision%20Dim&numbers=' . $phoneNumber . '&response=json';

    $response = Http::post($smsProviderUrl, [
        'numbers' => $phoneNumber,
        'message' => 'Your OTP is ' . $code . ' , valid until ' . Date('d-m-Y h:i A', strtotime("+15 minutes")),
        'sender' => $sender,
        'response' => 'json'
    ]);

    if ($response->getStatusCode() === 200) {
        // Successful API request
        return response()->json(['message' => 'OTP sent successfully','response'=>$response]);
    } else {
        // Failed API request
        return response()->json(['error' => 'Failed to send OTP', 'response'=>$response->getStatusCode()], 500);
    }
}

function sendSms($phoneNumber, $message, $sender = 'Vision Dim', $datetime = null, $return = 'text')
{
    // date time format  YYYY-MM-DD HH:MM:SS
    $smsApiToken = config('app.sms_api_token');
    $smsUsername = config('app.sms_username');
    $smsProviderUrl = 'https://www.mora-sa.com/api/v1/sendsms?
    api_key='.$smsApiToken.'&username='.$smsUsername.'
    &message=' . $message . '&sender='.$sender.'&numbers=' . $phoneNumber . '&response=' . $return;

    $client = new Client();
    $response = $client->post($smsProviderUrl, [
        'numbers' => $phoneNumber,
        'message' => $message,
        'sender' => $sender,
        'datetime' => $datetime,
        'return' => $return
    ]);

    if ($response->getStatusCode() === 200) {
        // Successful API request
        return response()->json(['message' => 'Message sent successfully']);
    }
    // Failed API request
    return response()->json(['error' => 'Failed to send Message'], 500);

}


function secondsToHours($seconds)
{
//    $carbon = Carbon::now()->addSeconds($seconds);
//    $hours = $carbon->hour;
//    $minutes = $carbon->minute;

    $hours = floor($seconds / 3600);
    $minutes = round(($seconds % 3600) / 60);

    return " $hours" ."H".":"."$minutes". "M";
}
function secondsToHoursDecimal($seconds)
{
//    $carbon = Carbon::now()->addSeconds($seconds);
//    $hours = $carbon->hour;
//    $minutes = $carbon->minute;

    $hours = floor($seconds / 3600);
    $minutes = round(($seconds % 3600) / 60);

    return "$hours" ."."."$minutes";
}
