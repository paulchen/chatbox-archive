<?php
require_once(dirname(__FILE__) . '/../lib/common.php');
require_once(dirname(__FILE__) . '/../lib/banana.php');

$rows = db_query("SELECT s.id shout_id, u.id AS id, s.message AS message, UNIX_TIMESTAMP(date) \"timestamp\"
		FROM shouts s
			JOIN users u ON (s.user_id = u.id)
		WHERE s.deleted = 0
			AND (s.message LIKE '%/trampolindb.gif%' OR s.message LIKE '%/devil-banana.gif%' OR s.message LIKE '%/turbo-devil-banana.gif%' OR s.message LIKE '%/extreme-turbo-devil-banana.gif%' OR s.message LIKE '%/NoDevilBanana.gif%' OR s.message LIKE '%/multihaildb.gif%')
		ORDER BY s.id ASC");
$result = calculate_bananas($rows);
$user_bananas = $result['user_bananas'];
$total = $result['total'];

$data = db_query('SELECT u.id AS id, u.name AS name, c.color AS color
		FROM users u
			JOIN user_categories c ON (u.category = c.id)');
$users = array();
foreach($data as $row) {
	if($row['color'] == '-') {
		$row['color'] = 'user';
	}
	if(isset($user_bananas[$row['id']])) {
		$user_bananas[$row['id']]['name'] = $row['name'];
		$user_bananas[$row['id']]['color'] = $row['color'];
	}
}

ob_start();
require_once(dirname(__FILE__) . '/../templates/pages/banana.php');
$data = ob_get_contents();
ob_clean();

xml_validate($data);
ob_start("ob_gzhandler");
echo $data;

log_data();

