<?php
define('IN_PHPBB', true);
$phpbb_root_path = '../phpbb/';
$phpbb_url_path = '/forum/';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
require($phpbb_root_path . 'common.' . $phpEx);

$user->session_begin();
$auth->acl($user->data);
$user->setup();

$portal_blocks = [
	'todays_choice' => [
		'title' => "Today's choice",
		'link' => $phpbb_url_path . 'viewtopic.php?t=1',
		'image' => './assets/placeholder-week.svg',
		'caption' => 'Vervang dit blok met de foto van vandaag.',
	],
	'yesterdays_choice' => [
		'title' => "Yesterday's choice",
		'link' => $phpbb_url_path . 'viewtopic.php?t=2',
		'image' => './assets/placeholder-week.svg',
		'caption' => 'Vervang dit blok met de foto van gisteren.',
	],
	'weekfoto' => [
		'title' => 'Weekfoto',
		'link' => $phpbb_url_path . 'viewtopic.php?t=3',
		'image' => './assets/placeholder-week.svg',
		'caption' => 'Vervang dit blok met de weekfoto winnaar.',
	],
	'maandopdracht' => [
		'title' => 'Maandopdracht',
		'link' => $phpbb_url_path . 'viewtopic.php?t=4',
		'image' => './assets/placeholder-month.svg',
		'caption' => 'Vervang dit blok met de maandopdracht.',
	],
	'challenge' => [
		'title' => 'Challenge',
		'link' => $phpbb_url_path . 'viewtopic.php?t=5',
		'image' => './assets/placeholder-month.svg',
		'caption' => 'Vervang dit blok met de huidige challenge.',
	],
];

$portal_blocks_path = __DIR__ . '/data/portal_blocks.json';
if (is_file($portal_blocks_path)) {
	$portal_blocks_data = json_decode((string) file_get_contents($portal_blocks_path), true);
	if (is_array($portal_blocks_data)) {
		foreach ($portal_blocks as $key => $defaults) {
			if (isset($portal_blocks_data[$key]) && is_array($portal_blocks_data[$key])) {
				$portal_blocks[$key] = array_merge($defaults, $portal_blocks_data[$key]);
			}
		}
	}
}

$latest_topics = [];
$sql = 'SELECT t.topic_id, t.topic_title, t.topic_last_post_time, t.topic_last_post_id, t.forum_id,
			t.topic_last_poster_id
		FROM ' . TOPICS_TABLE . ' t
		WHERE t.topic_visibility = 1
			AND t.topic_moved_id = 0
		ORDER BY t.topic_last_post_time DESC';
$result = $db->sql_query_limit($sql, 12);
while ($row = $db->sql_fetchrow($result)) {
	$latest_topics[] = $row;
}
$db->sql_freeresult($result);

$active_days = 7;
$active_topics = [];
$active_cutoff = time() - ($active_days * 86400);
$sql = 'SELECT t.topic_id, t.topic_title, t.topic_last_post_time
		FROM ' . TOPICS_TABLE . ' t
		WHERE t.topic_visibility = 1
			AND t.topic_moved_id = 0
			AND t.topic_last_post_time >= ' . (int) $active_cutoff . '
		ORDER BY t.topic_last_post_time DESC';
$result = $db->sql_query_limit($sql, 10);
while ($row = $db->sql_fetchrow($result)) {
	$active_topics[] = $row;
}
$db->sql_freeresult($result);

$top_topics = [];
$sql = 'SELECT t.topic_id, t.topic_title, t.topic_posts_approved, t.topic_last_post_time
		FROM ' . TOPICS_TABLE . ' t
		WHERE t.topic_visibility = 1
			AND t.topic_moved_id = 0
		ORDER BY t.topic_posts_approved DESC, t.topic_last_post_time DESC';
$result = $db->sql_query_limit($sql, 5);
while ($row = $db->sql_fetchrow($result)) {
	$top_topics[] = $row;
}
$db->sql_freeresult($result);

$online_users = obtain_users_online(0, 'forum');
$online_strings = obtain_users_online_string($online_users, 0, 'forum');
$online_list = $online_strings['online_userlist'];
$online_total = $online_strings['l_online_users'];

$phpbb_url_path = rtrim($phpbb_url_path, '/') . '/';
$login_action = append_sid("{$phpbb_url_path}ucp.$phpEx", 'mode=login');
$portal_title = 'Voorpagina';
$forum_url = append_sid("{$phpbb_url_path}index.$phpEx");
$register_url = append_sid("{$phpbb_url_path}ucp.$phpEx", 'mode=register');
$login_url = append_sid("{$phpbb_url_path}ucp.$phpEx", 'mode=login');
$search_url = append_sid("{$phpbb_url_path}search.$phpEx");
$faq_url = append_sid("{$phpbb_url_path}faq.$phpEx");
$rss_url = append_sid("{$phpbb_url_path}feed.$phpEx");

function portal_h($value)
{
	return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="nl">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo portal_h($portal_title); ?></title>
	<link rel="stylesheet" href="./assets/portal.css">
</head>
<body>
	<header class="portal-hero">
		<div class="portal-hero__inner">
			<div class="portal-brand">
				<img class="portal-brand__logo" src="./assets/logo-v2.png" alt="Photodrome logo">
			</div>
			<nav class="portal-nav">
				<a href="./">Voorpagina</a>
				<a href="<?php echo portal_h($login_url); ?>">Inloggen</a>
				<a href="<?php echo portal_h($register_url); ?>">Registreren</a>
				<a href="<?php echo portal_h($faq_url); ?>">Help</a>
				<a href="<?php echo portal_h($search_url); ?>">Zoeken</a>
				<a href="<?php echo portal_h($forum_url); ?>">Forum</a>
				<?php if ($auth->acl_get('a_') || $auth->acl_get('m_')) : ?>
					<a href="./admin.php">Beheer</a>
				<?php endif; ?>
			</nav>
		</div>
	</header>

	<main class="layout">
		<aside class="col col--left">
			<section class="panel">
				<h2 class="panel__title"><?php echo portal_h($portal_blocks['todays_choice']['title']); ?></h2>
				<a class="promo" href="<?php echo portal_h($portal_blocks['todays_choice']['link']); ?>">
					<img src="<?php echo portal_h($portal_blocks['todays_choice']['image']); ?>" alt="">
					<span><?php echo portal_h($portal_blocks['todays_choice']['caption']); ?></span>
				</a>
			</section>

			<section class="panel">
				<h2 class="panel__title"><?php echo portal_h($portal_blocks['yesterdays_choice']['title']); ?></h2>
				<a class="promo" href="<?php echo portal_h($portal_blocks['yesterdays_choice']['link']); ?>">
					<img src="<?php echo portal_h($portal_blocks['yesterdays_choice']['image']); ?>" alt="">
					<span><?php echo portal_h($portal_blocks['yesterdays_choice']['caption']); ?></span>
				</a>
			</section>

			<section class="panel">
				<h2 class="panel__title">Inloggen</h2>
				<form action="<?php echo portal_h($login_action); ?>" method="post">
					<label class="field">
						<span>Naam</span>
						<input type="text" name="username" autocomplete="username">
					</label>
					<label class="field">
						<span>Wachtwoord</span>
						<input type="password" name="password" autocomplete="current-password">
					</label>
					<label class="checkbox">
						<input type="checkbox" name="autologin">
						<span>Mij onthouden</span>
					</label>
					<input type="hidden" name="redirect" value="./">
					<button class="button" type="submit" name="login">Inloggen</button>
				</form>
			</section>

			<section class="panel">
				<h2 class="panel__title">Forum stats</h2>
				<ul class="stats">
					<li>Berichten: <strong><?php echo portal_h($config['num_posts']); ?></strong></li>
					<li>Onderwerpen: <strong><?php echo portal_h($config['num_topics']); ?></strong></li>
					<li>Leden: <strong><?php echo portal_h($config['num_users']); ?></strong></li>
				</ul>
			</section>
		</aside>

		<section class="col col--main">
			<section class="panel panel--feature">
				<h2 class="panel__title">Uitgelicht</h2>
				<div class="feature">
					<div class="feature__image"></div>
					<div class="feature__copy">
						<h3>Weekfoto</h3>
						<p>Plaats hier de winnaar van de weekfoto of een vaste promotie.</p>
						<a class="button button--ghost" href="<?php echo portal_h($forum_url); ?>">Naar het forum</a>
					</div>
				</div>
			</section>

			<section class="panel">
				<h2 class="panel__title"><?php echo portal_h($portal_blocks['weekfoto']['title']); ?></h2>
				<a class="promo" href="<?php echo portal_h($portal_blocks['weekfoto']['link']); ?>">
					<img src="<?php echo portal_h($portal_blocks['weekfoto']['image']); ?>" alt="">
					<span><?php echo portal_h($portal_blocks['weekfoto']['caption']); ?></span>
				</a>
			</section>

			<section class="panel">
				<h2 class="panel__title"><?php echo portal_h($portal_blocks['maandopdracht']['title']); ?></h2>
				<a class="promo" href="<?php echo portal_h($portal_blocks['maandopdracht']['link']); ?>">
					<img src="<?php echo portal_h($portal_blocks['maandopdracht']['image']); ?>" alt="">
					<span><?php echo portal_h($portal_blocks['maandopdracht']['caption']); ?></span>
				</a>
			</section>

			<section class="panel">
				<h2 class="panel__title"><?php echo portal_h($portal_blocks['challenge']['title']); ?></h2>
				<a class="promo" href="<?php echo portal_h($portal_blocks['challenge']['link']); ?>">
					<img src="<?php echo portal_h($portal_blocks['challenge']['image']); ?>" alt="">
					<span><?php echo portal_h($portal_blocks['challenge']['caption']); ?></span>
				</a>
			</section>

			<section class="panel">
				<h2 class="panel__title">Laatste onderwerpen</h2>
				<div class="topics">
					<?php foreach ($latest_topics as $topic) : ?>
						<a class="topic" href="<?php echo portal_h(append_sid("{$phpbb_url_path}viewtopic.$phpEx", 't=' . (int) $topic['topic_id'])); ?>">
							<span class="topic__title"><?php echo portal_h($topic['topic_title']); ?></span>
							<span class="topic__date"><?php echo portal_h($user->format_date((int) $topic['topic_last_post_time'], 'd M Y')); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</section>
		</section>

		<aside class="col col--right">
			<section class="panel">
				<h2 class="panel__title">Online</h2>
				<p class="muted"><?php echo $online_total; ?></p>
				<div class="online-list"><?php echo $online_list; ?></div>
			</section>

			<section class="panel">
				<h2 class="panel__title">Actieve onderwerpen</h2>
				<div class="topics topics--compact">
					<?php foreach ($active_topics as $topic) : ?>
						<a class="topic" href="<?php echo portal_h(append_sid("{$phpbb_url_path}viewtopic.$phpEx", 't=' . (int) $topic['topic_id'])); ?>">
							<span class="topic__title"><?php echo portal_h($topic['topic_title']); ?></span>
							<span class="topic__date"><?php echo portal_h($user->format_date((int) $topic['topic_last_post_time'], 'd M')); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</section>

			<section class="panel">
				<h2 class="panel__title">Top 5 onderwerpen</h2>
				<div class="topics topics--compact">
					<?php foreach ($top_topics as $topic) : ?>
						<a class="topic" href="<?php echo portal_h(append_sid("{$phpbb_url_path}viewtopic.$phpEx", 't=' . (int) $topic['topic_id'])); ?>">
							<span class="topic__title"><?php echo portal_h($topic['topic_title']); ?></span>
							<span class="topic__date"><?php echo max(0, (int) $topic['topic_posts_approved'] - 1); ?> reacties</span>
						</a>
					<?php endforeach; ?>
				</div>
			</section>

			<section class="panel">
				<h2 class="panel__title">Snel naar</h2>
				<ul class="links">
					<li><a href="<?php echo portal_h($forum_url); ?>">Forum overzicht</a></li>
					<li><a href="<?php echo portal_h($search_url); ?>">Zoeken</a></li>
					<li><a href="<?php echo portal_h($rss_url); ?>">RSS</a></li>
				</ul>
			</section>
		</aside>
	</main>

	<footer class="footer">
		<div>Powered by phpBB</div>
	</footer>
</body>
</html>
