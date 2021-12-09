<?php

namespace App\Http\Controllers;

use Exception;
use Facade\FlareClient\Http\Exceptions\MissingParameter;
use Illuminate\Http\Request;

class CheckController extends Controller
{
    public const LIMITS = [[0, 59], [0, 23], [1, 31], [1, 12], [0, 6]];

    /**
     * @throws MissingParameter
     * @throws Exception
     */
    public function check(Request $request): string
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

        for ($i = 0; $i < 5; $i++) {
            if (!$this->checkEach($template[$i], $value[$i], $i)) {
                return 'pattern does not match';
            }
        }

        return 'everything ok';
    }

    /**
     * @throws Exception
     */
    public function checkEach($check, $value, $type): bool
    {
        echo $check . ' ' . $value ;

        $allowed = [];

        if ($check === '*') {
            $allowed[] = $this->addAsterisk($type);
        }

        // digit only
        if (is_numeric($check)) {

            if ($check < self::LIMITS[$type][0] ||
                $check > self::LIMITS[$type][1]) {
                throw new Exception('first param is off the limits');
            }
            if ($value === $check) {
                return true;
            }
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
                $allowed[] = $this->addHyphen($check[$i], $type);
            }

            if (preg_match('/\//', $check[$i])) {
                $allowed[] = $this->addSlash($check[$i], $type);
            }

            if ($value === $check[$i]) {
                $allowed[] = [$value];
            }
        }

        $allowed = array_unique(array_merge(...$allowed));

        return in_array((int)$value, $allowed, true);
    }

    public function addAsterisk($type): array
    {
        $array = [];

        for ($i = 0; $i <= self::LIMITS[$type][1]; $i++) {
            $array[] = $i;
        }

        return $array;
    }

    /**
     * @throws Exception
     */
    public function addHyphen($template, $type): array
    {
        $array = [];
        $values = explode('-', $template);

        $this->checkRange($values, $type);

        for ($i = $values[0]; $i <= $values[1]; $i++) {
            $array[] = $i;
        }

        return $array;
    }

    /**
     * @throws Exception
     */
    public function addSlash($template, $type): array
    {
        $array = [];
        $values = explode('/', $template);

        $this->checkRange($values, $type);

        if ($values[0] === '*') {
            $values[0] = 0;
        }

        for ($i = $values[0]; $i <= self::LIMITS[$type][1]; $i++) {
            if ($i % $values[1] === 0) {
                $array[] = $i;
            }
        }

        return $array;
    }

    public function checkRange($values, $type): void
    {
        if ($values[0] < self::LIMITS[$type][0] ||
            $values[0] > self::LIMITS[$type][1] ||
            $values[1] < self::LIMITS[$type][0] ||
            $values[1] > self::LIMITS[$type][1]) {
            throw new Exception('first param is off the limits');
        }
    }
}
