<?php
// TODO check if included

require_once('common.php');

$last_update = -1;
foreach($queries as $index => $query) {
	if(isset($query['params'])) {
		$hash = sha1($query['query'] . serialize($query['params']));
	}
	else {
		$hash = sha1($query['query']);
	}
	$memcached_key = "${memcached_prefix}_stats_$hash";
	$memcached_data = $memcached->get($memcached_key);
	$memcached_data = null;
	if($memcached_data) {
		$last_update = max($memcached_data['update'], $last_update);
		$data = $memcached_data['data'];
	}
	else {
		$data = array();

		$stmt = $mysqli->prepare($query['query']);
		$ref = new ReflectionClass('mysqli_stmt');
		if(isset($query['params'])) {
			$args = array(str_repeat('s', count($query['params'])));
			foreach($query['params'] as $param) {
				$var = $param;
				$args[] = &$var;
				unset($var);
			}
			$method = $ref->getMethod('bind_param');
			$method->invokeArgs($stmt, $args);
		}
		$stmt->execute();

		$row = array();
		for($a=0; $a<$stmt->field_count; $a++) {
			$var = '';
			$row[] = &$var;
			unset($var);
		}
		$method = $ref->getMethod('bind_result');
		$method->invokeArgs($stmt, $row);

		$metadata = $stmt->result_metadata();
		$fields = $metadata->fetch_fields();

		while($stmt->fetch()) {
			$new_row = array();
			$a = 0;
			foreach($row as $cell) {
				$new_row[$fields[$a]->name] = $cell;
				$a++;
			}
			$data[] = $new_row;
		}

		$stmt->close();

		$memcached_data = array(
				'update' => time(),
				'data' => $data
			);
		// TODO magic number
		$memcached->set($memcached_key, $memcached_data, 300+rand(0,100));

		$last_update = time();
	}

	if(isset($query['processing_function'])) {
		call_user_func($query['processing_function'], array(&$data));
	}

	$queries[$index]['data'] = $data;
}

header('Content-Type: application/xhtml+xml; charset=utf-8');
echo '<?xml version="1.0" ?>';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<title><?php echo htmlentities($page_title, ENT_QUOTES, 'UTF-8') ?></title>
	<style type="text/css">
	body { font-family: Tahoma, Calibri, Verdana, Geneva, sans-serif; font-size: 13px; }
	table { border: none; }
	td.date, td.user { white-space: nowrap; }
	a { text-decoration: none; color: #417394; }
	a:hover { color: red; }
	a.user { color: #417394; }
	a.purple { color: purple; font-weight: bold; }
	a.green { color: green; font-weight: bold; }
	img { border: none; }
	th.left, td.left { text-align: left; }
	th.right, td.right { text-align: right; }
	</style>
</head>
<body>
	<h1><?php echo htmlentities($page_title, ENT_QUOTES, 'UTF-8') ?></h1>
	<div>
		<a href="<?php echo $backlink['url'] ?>"><?php echo htmlentities($backlink['text'], ENT_QUOTES, 'UTF-8') ?></a>
		<ul>
		<?php $b=0; foreach($queries as $query): $b++; ?>
			<li><a href="#query<?php echo $b; ?>"><?php echo htmlentities($query['title'], ENT_QUOTES, 'UTF-8') ?></a></li>
		<?php endforeach; ?>
		</ul>
	Last update: <?php echo date('Y-m-d H:i:s', $last_update) ?>
	</div>
	<hr />
	<?php $b=0; foreach($queries as $query): $b++; ?>
		<div>
			<a id="query<?php echo $b ?>"></a>
			<h2><?php echo $query['title'] ?></h2>
			<table><tr>
			<?php $a = 0; foreach($query['columns'] as $column): ?>
			<th class="<?php echo $query['column_styles'][$a] ?>"><?php echo $column; ?></th>
			<?php $a++; endforeach; ?>
			</tr>
			<?php
				foreach($query['data'] as $row):
					$a = 0;
			?>
				<tr>
				<?php foreach($row as $key => $value): ?>
					<!-- TODO <td class="<?php echo $query['column_styles'][$a] ?>"><?php echo htmlentities($value, ENT_QUOTES, 'UTF-8'); ?></td> -->
					<td class="<?php echo $query['column_styles'][$a] ?>"><?php echo $value ?></td>
				<?php $a++; endforeach; ?>
				</tr>
			<?php
				endforeach;
			?>
			</table>
		</div>
		<hr />
	<?php endforeach; ?>
	<p>
		<a href="http://validator.w3.org/check?uri=referer"><img src="xhtml.png" alt="Valid XHTML 1.1" height="31" width="88" /></a>
	</p>
</body>
</html>
