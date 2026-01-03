<?php
define('IN_PHPBB', true);
$phpbb_root_path = '../phpbb/';
$phpbb_url_path = '/forum/';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
require($phpbb_root_path . 'common.' . $phpEx);

$user->session_begin();
$auth->acl($user->data);
$user->setup();

function portal_h($value)
{
	return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function portal_form_token_fields($form_name)
{
	global $config, $user;

	$now = time();
	$token_sid = ($user->data['user_id'] == ANONYMOUS && !empty($config['form_token_sid_guests'])) ? $user->session_id : '';
	$token = sha1($now . $user->data['user_form_salt'] . $form_name . $token_sid);

	return build_hidden_fields([
		'creation_time' => $now,
		'form_token' => $token,
	]);
}

$is_moderator = $auth->acl_get('m_');
$is_admin = $auth->acl_get('a_');
$is_allowed = $user->data['is_registered'] && ($is_admin || $is_moderator);

if (!$is_allowed) {
	$login_url = append_sid("{$phpbb_url_path}ucp.$phpEx", 'mode=login&redirect=' . urlencode('/admin.php'));
	$message = $user->data['is_registered'] ? 'Je hebt geen toegang tot deze pagina.' : 'Log in om het portal te beheren.';
	?>
	<!doctype html>
	<html lang="nl">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Portal beheer</title>
		<link rel="stylesheet" href="./assets/portal.css">
	</head>
	<body>
		<header class="hero">
			<div class="hero__inner">
				<div class="brand">
					<div class="brand__title">Portal beheer</div>
					<div class="brand__subtitle">Toegang vereist</div>
				</div>
				<nav class="nav">
					<a href="./">Terug naar portal</a>
					<a href="<?php echo portal_h($phpbb_url_path); ?>">Forum</a>
				</nav>
			</div>
		</header>
		<main class="layout">
			<section class="col col--main">
				<section class="panel">
					<h2 class="panel__title">Toegang</h2>
					<p class="muted"><?php echo portal_h($message); ?></p>
					<?php if (!$user->data['is_registered']) : ?>
						<a class="button" href="<?php echo portal_h($login_url); ?>">Inloggen</a>
					<?php endif; ?>
				</section>
			</section>
		</main>
	</body>
	</html>
	<?php
	exit;
}

$portal_blocks_path = __DIR__ . '/data/portal_blocks.json';
$portal_blocks = [
	'uitgelicht' => [
		'title' => 'Uitgelicht maandopdracht',
		'link' => $phpbb_url_path . 'viewtopic.php?t=4',
		'image' => './assets/placeholder-month.svg',
		'caption' => 'De maandopdracht voor januari is: "Winter".',
	],
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

$request = $phpbb_container->get('request');
$save_message = '';
$form_token_fields = portal_form_token_fields('portal_blocks');

if ($request->is_set_post('submit')) {
	if (!check_form_key('portal_blocks')) {
		$save_message = 'Formulier verlopen, probeer opnieuw.';
	} else {
		$incoming = $request->raw_variable('blocks', []);
		$allowed_keys = array_keys($portal_blocks);
		foreach ($allowed_keys as $block_key) {
			if (!isset($incoming[$block_key]) || !is_array($incoming[$block_key])) {
				continue;
			}
			foreach (['title', 'link', 'image', 'caption'] as $field) {
				if (isset($incoming[$block_key][$field])) {
					$portal_blocks[$block_key][$field] = trim((string) $incoming[$block_key][$field]);
				}
			}
		}
		if (!is_dir(dirname($portal_blocks_path))) {
			mkdir(dirname($portal_blocks_path), 0775, true);
		}
		file_put_contents($portal_blocks_path, json_encode($portal_blocks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		$save_message = 'Opgeslagen.';
	}
}
?>
<!doctype html>
<html lang="nl">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Portal beheer</title>
	<link rel="stylesheet" href="./assets/portal.css">
</head>
<body class="admin-page">
	<header class="portal-hero">
		<div class="portal-hero__inner">
			<div class="portal-brand">
				<div class="portal-brand__title">Portal beheer</div>
				<div class="portal-brand__subtitle">Bewerk featured blokken</div>
			</div>
			<nav class="portal-nav">
				<a href="./">Terug naar portal</a>
				<a href="<?php echo portal_h($phpbb_url_path); ?>">Forum</a>
			</nav>
		</div>
	</header>

	<main class="layout">
		<section class="col col--main">
			<section class="panel">
				<h2 class="panel__title">Featured blokken</h2>
				<?php if ($save_message) : ?>
					<p class="muted"><?php echo portal_h($save_message); ?></p>
				<?php endif; ?>
				<form method="post">
					<div class="admin-grid">
						<?php foreach ($portal_blocks as $key => $block) : ?>
							<div class="panel admin-block">
								<h3 class="panel__title"><?php echo portal_h($block['title']); ?></h3>
								<label class="field">
									<span>Titel</span>
									<input type="text" name="blocks[<?php echo portal_h($key); ?>][title]" value="<?php echo portal_h($block['title']); ?>">
								</label>
								<label class="field">
									<span>Link (topic URL)</span>
									<input type="text" name="blocks[<?php echo portal_h($key); ?>][link]" value="<?php echo portal_h($block['link']); ?>">
								</label>
								<label class="field">
									<span>Afbeelding URL</span>
									<input type="text" name="blocks[<?php echo portal_h($key); ?>][image]" value="<?php echo portal_h($block['image']); ?>">
								</label>
								<label class="field">
									<span>Caption</span>
									<input type="text" name="blocks[<?php echo portal_h($key); ?>][caption]" value="<?php echo portal_h($block['caption']); ?>">
								</label>
							</div>
						<?php endforeach; ?>
					</div>
					<?php echo $form_token_fields; ?>
					<button class="button" type="submit" name="submit">Opslaan</button>
				</form>
			</section>
		</section>
	</main>
</body>
</html>
