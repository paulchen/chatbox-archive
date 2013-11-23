<?php
require_once(dirname(__FILE__) . '/../lib/common.php');
require_once(dirname(__FILE__) . '/../lib/ego.php');

$rows = db_query("SELECT s.id shout_id, u.id AS id, s.message AS message
		FROM shouts s
			JOIN users u ON (s.user = u.id)
		WHERE s.deleted = 0
			AND (s.message LIKE '%ego%' OR s.message LIKE '%/hail.gif%' OR s.message LIKE '%/multihail.gif%' OR s.message LIKE '%/antihail.png%')
		ORDER BY s.id ASC");
$result = calculate_ego($rows);
$user_egos = $result['user_egos'];
$available_ego = $result['available_ego'];

$data = db_query('SELECT u.id AS id, u.name AS name, c.color AS color
		FROM users u
			JOIN user_categories c ON (u.category = c.id)');
$users = array();
foreach($data as $row) {
	if($row['color'] == '-') {
		$row['color'] = 'user';
	}
	$users[$row['id']] = $row;
}

ob_start();
require_once(dirname(__FILE__) . '/../templates/pages/ego.php');
$data = ob_get_contents();
ob_clean();

xml_validate($data);
ob_start("ob_gzhandler");
echo $data;

log_data();

