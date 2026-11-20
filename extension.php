<?php

declare(strict_types=1);

/**
 * Cron Schedule: calendar-based scheduling (cron expression) of each feed's refresh.
 *
 * Principle: the FreshRSS cron (actualize_script.php) keeps running at a regular interval.
 * For each feed selected by FreshRSS, the feed_before_actualize hook checks whether an
 * occurrence of its cron expression has passed since its last refresh. Otherwise, the feed is skipped.
 */
final class CronScheduleExtension extends Minz_Extension {

	/** @var array<string,string> Invalid expressions entered (feed id => text), for redisplay. */
	public array $errors = [];

	/** @var array<string,CronScheduleExpression|null> */
	private array $parsed = [];

	#[\Override]
	public function init(): void {
		parent::init();
		require_once __DIR__ . '/CronScheduleExpression.php';
		$this->registerTranslates();

		$callback = [$this, 'onBeforeActualize'];
		try {
			$this->registerHook(Minz_HookType::FeedBeforeActualize, $callback);
		} catch (\Throwable) {
			// Older FreshRSS versions: hook declared by its name.
			$this->registerHook('feed_before_actualize', $callback);
		}
	}

	/** Returns the feed to refresh it, or null to skip it this time. */
	public function onBeforeActualize(FreshRSS_Feed $feed): ?FreshRSS_Feed {
		$expression = $this->schedules()[(string)$feed->id()] ?? '';
		if ($expression === '') {
			return $feed;	// unscheduled feed: normal FreshRSS behaviour
		}

		// Manual refresh (a feed's button, or ?force=1): never filtered.
		try {
			if (Minz_Request::paramInt('id') !== 0 || Minz_Request::paramBoolean('force')) {
				return $feed;
			}
		} catch (\Throwable) {
			// CLI context without a request: carry on.
		}

		$cron = $this->cron($expression);
		if ($cron === null) {
			return $feed;	// invalid expression: do not block the feed
		}
		$due = $cron->previous(time());
		if ($due === null) {
			return $feed;
		}
		return $feed->lastUpdate() < $due ? $feed : null;
	}

	/** @return array<string,string> feed id => cron expression */
	public function schedules(): array {
		$schedules = $this->getUserConfigurationValue('schedules', []);
		return is_array($schedules) ? $schedules : [];
	}

	public function cron(string $expression): ?CronScheduleExpression {
		return $this->parsed[$expression] ??= CronScheduleExpression::parse($expression);
	}

	/** @return list<FreshRSS_Feed> */
	public function feeds(): array {
		$feeds = array_values(FreshRSS_Factory::createFeedDao()->listFeeds());
		usort($feeds, static fn (FreshRSS_Feed $a, FreshRSS_Feed $b): int => strcasecmp($a->name(), $b->name()));
		return $feeds;
	}

	public function categoryName(FreshRSS_Feed $feed): string {
		try {
			return $feed->category()?->name() ?? '';
		} catch (\Throwable) {
			return '';
		}
	}

	#[\Override]
	public function handleConfigureAction(): void {
		require_once __DIR__ . '/CronScheduleExpression.php';
		$this->registerTranslates();

		if (!Minz_Request::isPost()) {
			return;
		}
		$knownIds = [];
		foreach ($this->feeds() as $feed) {
			$knownIds[(string)$feed->id()] = true;
		}

		$schedules = [];
		$this->errors = [];
		foreach (Minz_Request::paramArray('cron') as $id => $expression) {
			$id = (string)$id;
			$expression = trim(preg_replace('/\s+/', ' ', (string)$expression) ?? '');
			if ($expression === '' || !isset($knownIds[$id])) {
				continue;
			}
			if (CronScheduleExpression::parse($expression) === null) {
				$this->errors[$id] = $expression;
				continue;
			}
			$schedules[$id] = $expression;
		}
		$this->setUserConfiguration(['schedules' => $schedules]);
	}
}
