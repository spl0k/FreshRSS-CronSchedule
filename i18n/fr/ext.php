<?php

return [
	'cron_schedule' => [
		'title' => 'Planification des flux (cron)',
		'intro' => 'Indiquez, pour chaque flux, une expression cron (minute heure jour-du-mois mois jour-de-semaine). '
			. 'Les flux laissés vides gardent le comportement normal de FreshRSS.',
		'feed' => 'Flux',
		'expression' => 'Expression cron',
		'next' => 'Prochaine échéance',
		'invalid' => 'Certaines expressions sont invalides et n’ont pas été enregistrées.',
		'invalid_one' => 'Expression invalide',
		'help_title' => 'Mode d’emploi',
		'help' => '<ul>'
			. '<li><code>0 7 * * 1-5</code> : à 7h00 du lundi au vendredi</li>'
			. '<li><code>0 7,18 * * *</code> : tous les jours à 7h00 et 18h00</li>'
			. '<li><code>*/10 7 * * 1-5</code> : toutes les 10 min entre 7h00 et 7h50 en semaine (fenêtre de tentatives)</li>'
			. '<li><code>30 12 1 * *</code> : le 1er de chaque mois à 12h30</li>'
			. '</ul>'
			. '<p>Pour que l’échéance soit respectée à quelques minutes près, réglez la durée minimale entre deux '
			. 'actualisations du flux sur la plus courte valeur proposée, et vérifiez que le cron de FreshRSS tourne '
			. 'assez souvent. Les actualisations manuelles ne sont jamais bloquées.</p>',
	],
];
