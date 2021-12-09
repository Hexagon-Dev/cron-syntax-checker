<?php

namespace App\Http\Controllers;

use Exception;
use Facade\FlareClient\Http\Exceptions\MissingParameter;
use Illuminate\Http\Request;

class CheckController extends Controller
{
    /**
     * @throws MissingParameter
     * @throws Exception
     */
    public function check(Request $request)
    {
        $template = $request->input('template');
        $value = $request->input('value');

        if (date_parse_from_format('Y m d H i', $value)['warning_count'] > 0) {
            throw new Exception('invalid date');
        }

        $template = explode(' ', $template);
        $value = explode(' ', $value);
        $value = array_reverse($value);

        if (count($template) < 5 || count($value) < 5) {
            throw new Exception('not enough parameters');
        } else if (count($template) > 5 || count($value) > 5){
            throw new Exception('too many parameters');
        }

        $value[4] = date('w', strtotime($value[0] . '-' . $value[1] . '-' . $value[2]));

        $limits = [[0, 59], [0, 24], [1, 31], [1, 12], [0, 6]];

        echo '
         <head>
          <style>
           TABLE {
            border-collapse: collapse;
            border: 2px solid black;
           }
           TD, TH {
            border: 1px solid black;
            text-align: left;
            padding: 10px;
           }
          </style>
         </head>
         <body><table>';

        for ($i = 0; $i < 5; $i++) {
            if (!$this->checkEach($template[$i], $value[$i], $limits[$i])) {
                return 'pattern does not match';
            }
        }
        echo '</table><br></body>';

        return 'everything ok';
    }

    /**
     * @throws Exception
     */
    public function checkEach($check, $value, $limits)
    {
        echo '<tr><td>' . $check . '</td><td>' . $value . '</td></tr>';

        // all
        if (strpos($check, "*") === 0) {
            return true;
        }

        // digit only
        if (is_numeric($check)) {
            if ($check < $limits[0] || $check > $limits[1]) {
                throw new Exception('cron pattern limit failed');
            }
            return $value === $check;
        }

        // ,
        if (preg_match('/,/', $check)) {
            $check = explode(',', $check);
            $size = count($check);
        } else {
            $check = [$check];
            $size = 1;
        }

        for ($i = 0; $i < $size; $i++) {
            if (false !== strpos($check[$i], "-")) {
                $values = explode('-', $check[$i]);

                if ($values[0] < $limits[0] && $values[1] < $limits[0] && $check[0] > $limits[1] && $check[1] > $limits[1]) {
                    throw new Exception('cron pattern limit failed');
                }
                return !($value < $values[0] || $value > $values[1]);
            }
            if (preg_match('/\//', $check[$i])) {
                $values = explode('/', $check[$i]);
                if ($values[0] < $limits[0] && $values[1] < $limits[0] && $check[0] > $limits[1] && $check[1] > $limits[1]) {
                    throw new Exception('cron pattern limit failed');
                }
                return !($value < $values[0] || $value % $values[1] !== 0);
            }
            if (!is_numeric($check[$i])) {
                return false;
            }

            if ($value === $check[$i]) {
                return true;
            }
        }

        return false;
    }
}
