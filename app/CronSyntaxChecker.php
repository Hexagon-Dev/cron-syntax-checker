<?php

namespace App;

use DateTimeInterface;
use Exception;
use Throwable;

class CronSyntaxChecker
{
    public const LIMITS = [[0, 59], [0, 23], [1, 31], [1, 12], [0, 6]];

    /**
     * @throws Throwable
     */
    public function process($template, DateTimeInterface $dateTime): string
    {
        $dateTimeArr = date_parse_from_format('Y-m-d H:i:s', $dateTime->format('Y-m-d H:i:s'));
        $dateTimeArr = [$dateTimeArr['minute'], $dateTimeArr['hour'], $dateTimeArr['day'], $dateTimeArr['month'], $dateTime->format('w'),];
        $template = explode(' ', $template);

        for ($i = 0, $size = count($template); $i < $size; $i++) {
            if (!$this->checkEach($template[$i], $dateTimeArr[$i], $i)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws Throwable
     */
    public function checkEach($partition, $value, $type): bool
    {
        $allowed = [];

        if ($partition === '*') {
            return true;
        }

        if (is_numeric($partition)) {
            if ($partition < self::LIMITS[$type][0] || $partition > self::LIMITS[$type][1]) {
                throw new Exception('template is off the limits');
            }
            if ($value === (int)$partition) {
                return true;
            }
        }

        foreach ($this->splitCommaValues($partition) as $part) {
            if (str_contains($part, '/')) {
                $allowed[] = $this->parseSlash($part, $type);
                continue;
            }
            if (str_contains($part, "-")) {
                $allowed[] = $this->parseHyphen($part, $type);
                continue;
            }
            if ($value === (int)$part) {
                $allowed[] = [$value];
                continue;
            }

            //new Exception()
        }

        return in_array((int)$value, array_unique(array_merge(...$allowed)), true);
    }

    public function splitCommaValues($check): array
    {
        return explode(',', $check);
    }

    /**
     * @throws Throwable
     */
    public function parseHyphen($template, $type): array
    {
        $values = explode('-', $template);

        $this->checkRange($values, $type);

        return range($values[0], $values[1]);
    }

    /**
     * @throws Throwable
     */
    public function parseSlash($template, $type): array
    {
        $array = [];
        $values = explode('/', $template);

        $this->checkRange($values, $type);

        $values[0] = $values[0] === '*' ? 0 : $values[0];

        for ($i = $values[0]; $i <= self::LIMITS[$type][1]; $i++) {
            if ($i % $values[1] === 0) {
                $array[] = $i;
            }
        }

        return $array;
    }

    /**
     * @throws Throwable
     */
    public function checkRange($value, $type): void
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        foreach ($value as $var) {
            if (!str_contains($var, '*') && !in_array((int)$var, range(...self::LIMITS[$type]), true)) {
                throw new Exception('param is off the limits');
            }
        }
    }
}
