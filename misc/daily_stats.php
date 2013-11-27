<?php
require_once(dirname(__FILE__) . '/../lib/common.php');

$data = db_query("SELECT u.name AS name, COUNT(*) count FROM shouts s JOIN users u ON (s.user=u.id) WHERE AGE(s.date) < interval '1 days' GROUP BY u.id, u.name ORDER BY count DESC");
$total = 0;
$top_spammers = '';
foreach($data as $index => $row) {
	$total += $row['count'];
	if($index < 5) {
		if($top_spammers != '') {
			$top_spammers .= ', ';
		}
		$top_spammers .= ($index+1) . '. ' . $row['name'] . ' (' . $row['count'] . ')';
	}
}
echo rawurlencode("Messages in the last 24 hours: $total; top spammers: $top_spammers");

