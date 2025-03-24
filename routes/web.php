<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {

    $countrie = \Modules\Country\Models\Country::all();
    foreach ($countrie as $country) {
        $universities = \Modules\Shared\University\Models\University::query()->where('country_id', $country->id)->get();
        foreach ($universities as $university) {
            $university->update(["country_iso2"=>$country->iso2]);

      }
    }
    return view('welcome');
});
