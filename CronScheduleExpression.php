<?php

declare(strict_types=1);

/**
 * 5-field cron expression (minute hour day-of-month month day-of-week).
 * Supports: *, lists (1,5), ranges (1-5), steps (* /10, 1-30/5),
 * month/day names (jan, mon) and macros (@hourly, @daily, @weekly, @monthly, @yearly).
 */
final class CronScheduleExpression {

	private const MAX_DAYS = 1500;

	private const MACROS = [
		'@hourly' => '0 * * * *',
		'@daily' => '0 0 * * *',
		'@midnight' => '0 0 * * *',
		'@weekly' => '0 0 * * 0',
		'@monthly' => '0 0 1 * *',
		'@yearly' => '0 0 1 1 *',
		'@annually' => '0 0 1 1 *',
	];

	private const MONTHS = ['jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
		'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12];
	private const DAYS = ['sun' => 0, 'mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6];

	/** @param array<int,true> $minutes
	 *  @param array<int,true> $hours
	 *  @param array<int,true> $doms
	 *  @param array<int,true> $months
	 *  @param array<int,true> $dows */
	private function __construct(
		private array $minutes,
		private array $hours,
		private array $doms,
		private array $months,
		private array $dows,
		private bool $domStar,
		private bool $dowStar,
	) {}

	public static function parse(string $expression): ?self {
		$expression = trim(preg_replace('/\s+/', ' ', $expression) ?? '');
		$expression = self::MACROS[strtolower($expression)] ?? $expression;
		$fields = explode(' ', $expression);
		if (count($fields) !== 5) {
			return null;
		}
		$minutes = self::parseField($fields[0], 0, 59);
		$hours = self::parseField($fields[1], 0, 23);
		$doms = self::parseField($fields[2], 1, 31);
		$months = self::parseField($fields[3], 1, 12, self::MONTHS);
		$dows = self::parseField($fields[4], 0, 7, self::DAYS);
		if ($minutes === null || $hours === null || $doms === null || $months === null || $dows === null) {
			return null;
		}
		if (isset($dows[7])) {	// 7 = Sunday, like 0
			unset($dows[7]);
			$dows[0] = true;
		}
		return new self($minutes, $hours, $doms, $months, $dows,
			str_starts_with($fields[2], '*'), str_starts_with($fields[4], '*'));
	}

	/**
	 * @param array<string,int> $names
	 * @return array<int,true>|null
	 */
	private static function parseField(string $field, int $min, int $max, array $names = []): ?array {
		$field = strtolower($field);
		$values = [];
		foreach (explode(',', $field) as $part) {
			if ($part === '') {
				return null;
			}
			$step = 1;
			if (str_contains($part, '/')) {
				$pieces = explode('/', $part);
				if (count($pieces) !== 2 || !ctype_digit($pieces[1]) || (int)$pieces[1] < 1) {
					return null;
				}
				$part = $pieces[0];
				$step = (int)$pieces[1];
			}
			if ($part === '*') {
				$from = $min;
				$to = ($max === 7) ? 6 : $max;	// for day-of-week, * = 0-6
			} elseif (str_contains($part, '-')) {
				$bounds = explode('-', $part);
				if (count($bounds) !== 2) {
					return null;
				}
				$from = self::toInt($bounds[0], $names);
				$to = self::toInt($bounds[1], $names);
			} else {
				$from = self::toInt($part, $names);
				$to = str_contains($field, '/') && $step > 1 ? $max : $from;	// "5/10" = 5-max/10
			}
			if ($from === null || $to === null || $from < $min || $to > $max || $from > $to) {
				return null;
			}
			for ($i = $from; $i <= $to; $i += $step) {
				$values[$i] = true;
			}
		}
		return $values === [] ? null : $values;
	}

	/** @param array<string,int> $names */
	private static function toInt(string $value, array $names): ?int {
		if (ctype_digit($value)) {
			return (int)$value;
		}
		return $names[$value] ?? null;
	}

	private function matchesDay(DateTimeImmutable $day): bool {
		if (!isset($this->months[(int)$day->format('n')])) {
			return false;
		}
		$domOk = isset($this->doms[(int)$day->format('j')]);
		$dowOk = isset($this->dows[(int)$day->format('w')]);
		return ($this->domStar || $this->dowStar) ? ($domOk && $dowOk) : ($domOk || $dowOk);
	}

	private static function at(int $timestamp): DateTimeImmutable {
		return (new DateTimeImmutable('@' . $timestamp))->setTimezone(new DateTimeZone(date_default_timezone_get()));
	}

	/** Last occurrence <= $now (timestamp), or null if none in the last ~4 years. */
	public function previous(int $now): ?int {
		$t = self::at($now);
		$day = $t->setTime(0, 0);
		for ($i = 0; $i < self::MAX_DAYS; $i++) {
			if ($this->matchesDay($day)) {
				$maxHour = $i === 0 ? (int)$t->format('G') : 23;
				for ($h = $maxHour; $h >= 0; $h--) {
					if (!isset($this->hours[$h])) {
						continue;
					}
					$maxMin = ($i === 0 && $h === $maxHour) ? (int)$t->format('i') : 59;
					for ($m = $maxMin; $m >= 0; $m--) {
						if (isset($this->minutes[$m])) {
							return $day->setTime($h, $m)->getTimestamp();
						}
					}
				}
			}
			$day = $day->modify('-1 day');
		}
		return null;
	}

	/** Next occurrence > $now (timestamp), or null. */
	public function next(int $now): ?int {
		$t = self::at($now);
		$day = $t->setTime(0, 0);
		for ($i = 0; $i < self::MAX_DAYS; $i++) {
			if ($this->matchesDay($day)) {
				for ($h = 0; $h <= 23; $h++) {
					if (!isset($this->hours[$h])) {
						continue;
					}
					for ($m = 0; $m <= 59; $m++) {
						if (!isset($this->minutes[$m])) {
							continue;
						}
						$ts = $day->setTime($h, $m)->getTimestamp();
						if ($ts > $now) {
							return $ts;
						}
					}
				}
			}
			$day = $day->modify('+1 day');
		}
		return null;
	}
}
