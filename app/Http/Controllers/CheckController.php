<?php

namespace App\Http\Controllers;

class CheckController extends Controller
{
    public function check($value)
    {

        $value = explode(' ', $value);

        [$minutes, $hour, $day_month, $month, $day_week] = $value;

        echo $minutes;
    }
}
