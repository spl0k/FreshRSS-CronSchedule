<?php

return [
	'cron_schedule' => [
		'title' => 'Feed scheduling (cron)',
		'intro' => 'Enter a cron expression (minute hour day-of-month month day-of-week) for each feed. '
			. 'Feeds left empty keep the normal FreshRSS behaviour.',
		'feed' => 'Feed',
		'expression' => 'Cron expression',
		'next' => 'Next run',
		'invalid' => 'Some expressions are invalid and were not saved.',
		'invalid_one' => 'Invalid expression',
		'help_title' => 'Help',
		'help' => '<ul>'
			. '<li><code>0 7 * * 1-5</code>: at 07:00, Monday to Friday</li>'
			. '<li><code>0 7,18 * * *</code>: every day at 07:00 and 18:00</li>'
			. '<li><code>*/10 7 * * 1-5</code>: every 10 min from 07:00 to 07:50 on weekdays (retry window)</li>'
			. '<li><code>30 12 1 * *</code>: on the 1st of each month at 12:30</li>'
			. '</ul>'
			. '<p>For the schedule to be honoured within a few minutes, set the feed\'s minimum refresh interval to the '
			. 'shortest available value, and make sure the FreshRSS cron runs often enough. Manual refreshes are never blocked.</p>',
	],
];
