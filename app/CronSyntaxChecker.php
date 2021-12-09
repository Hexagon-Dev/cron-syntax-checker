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
    public function checkEach($template, $value, $type): bool
    {
        $allowed = [];

        if ($template === '*') {
            return true;
        }

        if (is_numeric($template)) {
            if ($template < self::LIMITS[$type][0] || $template > self::LIMITS[$type][1]) {
                throw new Exception('template is off the limits');
            }
            if ($value === (int)$template) {
                return true;
            }
        }

        foreach ($this->splitCommaValues($template) as $part) {
            if (false !== strpos($part, "-")) {
                $allowed[] = $this->addHyphen($part, $type);
            }
            if (preg_match('/\//', $part)) {
                $allowed[] = $this->addSlash($part, $type);
            }
            if ($value === (int)$part) {
                $allowed[] = [$value];
            }
        }

        return in_array((int)$value, array_unique(array_merge(...$allowed)), true);
    }

    public function splitCommaValues($check): array
    {
        if (preg_match('/,/', $check)) {
            return explode(',', $check);
        }

        return [$check];
    }

    /**
     * @throws Throwable
     */
    public function addHyphen($template, $type): array
    {
        $values = explode('-', $template);

        $this->checkRange($values, $type);

        return range($values[0], $values[1]);
    }

    /**
     * @throws Throwable
     */
    public function addSlash($template, $type): array
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
    public function checkRange($values, $type): void
    {
        $values[0] = $values[0] === '*' ? 0 : $values[0];

        if (!in_array((int)$values[0], range(self::LIMITS[$type][0], self::LIMITS[$type][1]), true) &&
            !in_array((int)$values[1], range(self::LIMITS[$type][0], self::LIMITS[$type][1]), true)) {
            throw new Exception('param is off the limits');
        }
    }
}
