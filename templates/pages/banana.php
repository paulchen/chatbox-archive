<?php
echo '<?xml version="1.0" ?>';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dth">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<!-- TODO HTML tags inside page title -->
	<title>Devil bananas</title>
	<style type="text/css">
	body { font-family: Tahoma, Calibri, Verdana, Geneva, sans-serif; font-size: 13px; }
	table { border: none; }
	th.date, td.user { white-space: nowrap; }
	a { text-decoration: none; color: #417394; }
	h1 > a { color: black; }
	a:hover { color: red; }
	a.user { color: #417394; }
	a.purple { color: purple; font-weight: bold; }
	a.green { color: green; font-weight: bold; }
	img { border: none; }
	th.left, th.left { text-align: left; }
	th, td { text-align: right; }
	td:first-child, th:first-child { text-align: left; }
	th.center { text-align: center; }
	</style>
	<script type="text/javascript" src="js/jquery.min.js"></script>
	<script type="text/javascript" src="js/jquery.tablesorter.min.js"></script>
	<script type="text/javascript">
$(document).ready(function() {
	$('.sortable_table').tablesorter();
});
	</script>
</head>
<body>
	<h1><a href="banana.php">Devil bananas</a></h1>
	<div>
		<a href="./">Chatbox archive</a>
	</div>
	<hr />
	<div>
		<h3>Top banana posters</h3>
		This table is sortable. Click on the column header to sort by the appropriate column.
		<table class="sortable_table">
			<thead>
			<tr><th class="left">User</th><th colspan="5" class="center">Bananas posted</th><th colspan="5" class="center">Bananas neutralized</th><th colspan="3" class="center">Time until neutralization (minutes)</th></tr>
			<tr>
				<th></th>
				<th>Total</th>
				<th><img src="images/smilies/devil-banana.gif" alt=":db:" title=":db:" /></th>
				<th><img src="images/smilies/trampolindb.gif" alt=":trampolindb:" title=":trampolindb:" /></th>
				<th><img src="images/smilies/turbo-devil-banana.gif" alt=":turbodb:" title=":turbodb:" /></th>
				<th><img src="images/smilies/extreme-turbo-devil-banana.gif" alt=":extremeturbodb:" title=":extremeturbodb:" /></th>
				<th>Total</th>
				<th><img src="images/smilies/devil-banana.gif" alt=":db:" title=":db:" /></th>
				<th><img src="images/smilies/trampolindb.gif" alt=":trampolindb:" title=":trampolindb:" /></th>
				<th><img src="images/smilies/turbo-devil-banana.gif" alt=":turbodb:" title=":turbodb:" /></th>
				<th><img src="images/smilies/extreme-turbo-devil-banana.gif" alt=":extremeturbodb:" title=":extremeturbodb:" /></th>
				<th>min</th>
				<th>max</th>
				<th>avg</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach($user_bananas as $id => $data): ?>
				<tr>
					<td class="left"><a class="<?php echo $data['color'] ?>" href="./?user=<?php echo urlencode($data['name']) ?>&amp;limit=100&amp;page=1&amp;date=&amp;refresh=on"><?php echo $data['name']; ?></a></td>
					<td><?php echo $data['total_added'] ?></td>
					<td><?php echo isset($data['bananas_added']['devil-banana']) ? $data['bananas_added']['devil-banana'] : 0 ?></td>
					<td><?php echo isset($data['bananas_added']['trampolindb']) ? $data['bananas_added']['trampolindb'] : 0 ?></td>
					<td><?php echo isset($data['bananas_added']['turbo-devil-banana']) ? $data['bananas_added']['turbo-devil-banana'] : 0 ?></td>
					<td><?php echo isset($data['bananas_added']['extreme-turbo-devil-banana']) ? $data['bananas_added']['extreme-turbo-devil-banana'] : 0 ?></td>
					<td><?php echo $data['total_annihilated'] ?></td>
					<td><?php echo isset($data['bananas_annihilated']['devil-banana']) ? $data['bananas_annihilated']['devil-banana'] : 0 ?></td>
					<td><?php echo isset($data['bananas_annihilated']['trampolindb']) ? $data['bananas_annihilated']['trampolindb'] : 0 ?></td>
					<td><?php echo isset($data['bananas_annihilated']['turbo-devil-banana']) ? $data['bananas_annihilated']['turbo-devil-banana'] : 0 ?></td>
					<td><?php echo isset($data['bananas_annihilated']['extreme-turbo-devil-banana']) ? $data['bananas_annihilated']['extreme-turbo-devil-banana'] : 0 ?></td>
					<td><?php echo ($data['total_annihilated'] == 0) ? '-' : $data['times']['min'] ?></td>
					<td><?php echo ($data['total_annihilated'] == 0) ? '-' : $data['times']['max'] ?></td>
					<td><?php echo ($data['total_annihilated'] == 0) ? '-' : $data['times']['avg'] ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
			<tfoot>
			<tr>
				<th class="left">Total</th>
				<th><?php echo $total['total_added'] ?></th>
				<th><?php echo isset($total['bananas_added']['devil-banana']) ? $total['bananas_added']['devil-banana'] : 0 ?></th>
				<th><?php echo isset($total['bananas_added']['trampolindb']) ? $total['bananas_added']['trampolindb'] : 0 ?></th>
				<th><?php echo isset($total['bananas_added']['turbo-devil-banana']) ? $total['bananas_added']['turbo-devil-banana'] : 0 ?></th>
				<th><?php echo isset($total['bananas_added']['extreme-turbo-devil-banana']) ? $total['bananas_added']['extreme-turbo-devil-banana'] : 0 ?></th>
				<th><?php echo $total['total_annihilated'] ?></th>
				<th><?php echo isset($total['bananas_annihilated']['devil-banana']) ? $total['bananas_annihilated']['devil-banana'] : 0 ?></th>
				<th><?php echo isset($total['bananas_annihilated']['trampolindb']) ? $total['bananas_annihilated']['trampolindb'] : 0 ?></th>
				<th><?php echo isset($total['bananas_annihilated']['turbo-devil-banana']) ? $total['bananas_annihilated']['turbo-devil-banana'] : 0 ?></th>
				<th><?php echo isset($total['bananas_annihilated']['extreme-turbo-devil-banana']) ? $total['bananas_annihilated']['extreme-turbo-devil-banana'] : 0 ?></th>
				<th><?php echo ($total['total_annihilated'] == 0) ? '-' : $total['times']['min'] ?></th>
				<th><?php echo ($total['total_annihilated'] == 0) ? '-' : $total['times']['max'] ?></th>
				<th><?php echo ($total['total_annihilated'] == 0) ? '-' : $total['times']['avg'] ?></th>
			</tr>
			</tfoot>
		</table>
	</div>
	<hr />
	<p>
		<a href="http://validator.w3.org/check?uri=referer"><img src="images/xhtml.png" alt="Valid XHTML 1.1" height="31" width="88" /></a>
	</p>
</body>
</html>
