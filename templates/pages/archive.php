<?php
if(!$ajax):
	echo '<?xml version="1.0" ?>';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<title>Chatbox archive</title>
	<style type="text/css">
	body { font-family: Tahoma, Calibri, Verdana, Geneva, sans-serif; font-size: 13px; }
	table { border: none; }
	td.date, td.user { white-space: nowrap; }
	a { text-decoration: none; color: #417394; }
	h1 > a { color: black; }
	a.user, span.user { color: #417394; }
	a.purple, span.purple { color: purple; font-weight: bold; }
	a.green, span.green { color: green; font-weight: bold; }
	a.red, span.red { color: red; font-weight: bold; }
	a.blue, span.blue { color: blue; font-weight: bold; }
	td.date > a { color: black; }
	td.date > a:hover { color: red; }
	a:hover { color: red; }
	img { border: none; }
	a.revisions { color: #666; font-size: 80%; }
	a.revisions:hover { text-decoration: underline; }
	td {
		padding: 2px;
	}
	</style>
        <link href="css/redmond/jquery-ui-1.10.0.custom.min.css" rel="stylesheet" type="text/css"></link>
	<script type="text/javascript" src="js/jquery.min.js"></script>
        <script type="text/javascript" src="js/jquery-ui-1.10.0.custom.min.js"></script>
	<script src="js/jQuery.bubbletip-1.0.6.js" type="text/javascript"></script>
	<link href="css/bubbletip/bubbletip.css" rel="stylesheet" type="text/css" />
	<!--[if IE]>
	<link href="css/bubbletip/bubbletip-IE.css" rel="stylesheet" type="text/css" />
	<![endif]-->
	<script type="text/javascript">
<!--
var timeout;

function refresh() {
	var url = document.location.href;
	if(url.indexOf('#') > -1) {
		url = url.substring(0, url.indexOf('#'));
	}
	if(url.indexOf('?') == -1) {
		url += "?ajax=on";
	}
	else {
		url += "&ajax=on";
	}

	$.ajax({
		url : url,
		success : function(data, textStatus, xhr) {
			if($('table.bubbletip').size() == 0 || $('table.bubbletip').css('display') == 'none') {
				var pos = data.indexOf('$$');
				var parts = data.substring(0, pos).split(' ');
				data = data.substring(pos+2);

				pos = data.indexOf('$$');
				var ids = data.substring(0, pos).split(' ');
				data = data.substring(pos+2);
				$('.revisions').removeBubbletip();

				$('#content').children().remove();
				$('#content').append(data);
				$('#shouts_filtered').text(parts[1]);
				$('#shouts_total').text(parts[2]);
				$('.page_count').text(parts[0]);
				$('.next_link').attr('href', "<?php echo $generic_link ?>" + Math.min(parts[0], <?php echo $page+1 ?>));
				$('.last_link').attr('href', "<?php echo $generic_link ?>" + parts[0]);

				$.each(ids, function(index, value) {
					if(value != '') {
						bubbletip(value);
					}
				});
			}
		},
		complete : function(xhr, textStatus) {
			update_refresh();
		}
	});
}

function update_refresh() {
	clearTimeout(timeout);
	if($('#refresh_checkbox').is(':checked')) {
		timeout = setTimeout('refresh();', <?php echo $refresh_time*1000 ?>);
	}
}

function reset_form() {
	if($('#refresh_checkbox').is(':checked')) {
		document.location.href = '?refresh=on';
	}
	else {
		document.location.href = '?';
	}
}

function bubbletip(id) {
	$('#revisions_link_' + id).bubbletip($('#revisions_' + id));
}

function highlight(id, color, initial_wait, step_wait, step) {
	$(window.location.hash).parents('tr').css('background-color', '#' + color.toString(16));
	if(color < 0xFFFFFF) {
		new_color = color+step;
		if(new_color > 0xFFFFFF) {
			new_color = 0xFFFFFF;
		}
		window.setTimeout('highlight("' + id + '",' + new_color + ',0,' + step_wait + ',' + step + ');', initial_wait+step_wait);
	}
}

$(document).ready(function() {
	if(window.location.hash != '') {
		highlight(window.location.hash, 0xFFFF00, 3000, 100, 0x000005);
	}
	update_refresh();

	$('#refresh_checkbox').change(function() {
		update_refresh();
	});

	$('#name_input').autocomplete({
		source : <?php echo $users; ?>,
	});

	$('#date_input').datepicker({
		firstDay : 1,
		dateFormat : 'yy-mm-dd',
	});

	<?php foreach($messages as $message): ?>
		<?php if(count($message['revisions']) > 0): ?>
			bubbletip('<?php echo $message['id'] . '_' . $message['epoch'] ?>');
		<?php endif; ?>
	<?php endforeach; ?>
});

// -->
	</script>
</head>
<body>
	<h1><a href="index.php">Chatbox archive</a></h1>
	<div>
		<a href="details.php">Spam overview (all time)</a>
		<a href="details.php?period=forum">Spam overview (all messages on informatik-forum.at)</a>
		<fieldset><legend>Filters</legend>
		<form method="get" action="<?php echo htmlentities($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>">
		<table>
		<tr><td>Text:</td><td><input type="text" name="text" value="<?php if(isset($_GET['text'])) echo htmlentities($_GET['text'], ENT_QUOTES, 'UTF-8') ?>" /></td></tr>
		<tr><td>User:</td><td><input type="text" name="user" value="<?php if(isset($_GET['user'])) echo htmlentities($_GET['user'], ENT_QUOTES, 'UTF-8') ?>" id="name_input" /></td></tr>
		<tr><td>Messages per page:</td><td><input type="text" name="limit" value="<?php echo $limit; ?>" /></td></tr>
		<tr><td>Page:</td><td style="white-space: nowrap;"><input type="text" name="page" value="<?php echo $page; ?>" /> (of <span class="page_count"><?php echo $page_count; ?></span>) <a href="<?php echo $first_link ?>">First</a> <a href="<?php echo $previous_link ?>">Previous</a> <a href="<?php echo $next_link ?>" class="next_link">Next</a> <a href="<?php echo $last_link ?>" class="last_link">Last</a></td></tr>
		<tr><td>Date:</td><td><input type="text" name="date" value="<?php if(isset($_GET['date'])) echo htmlentities($_GET['date'], ENT_QUOTES, 'UTF-8') ?>" id="date_input" /></td></tr>
		<tr><td></td><td><input type="submit" value="Filter" /><input type="button" value="Reset" onclick="reset_form();" /></td></tr>
		<tr><td></td><td><input id="refresh_checkbox" type="checkbox" name="refresh" <?php if($refresh) echo 'checked="checked"'; ?> />&nbsp;<label for="refresh_checkbox">Auto-refresh every <?php echo $refresh_time ?> seconds.</label></td></tr>
		</table>
		</form>
		</fieldset>
		<div style="padding: 10px 5px 10px 5px;">
			Messages (filtered/total): <span id="shouts_filtered"><?php echo $filtered_shouts ?></span>/<span id="shouts_total"><?php echo $total_shouts ?></span>
		</div>
		<div id="content">
<?php else:
	echo "$page_count $filtered_shouts $total_shouts$$";
	foreach($messages as $message):
		if(count($message['revisions']) > 0):
			echo $message['id'] . '_' . $message['epoch'] . ' ';
		endif;
	endforeach;
	echo '$$';
endif; /* if(!$ajax) */ ?>
			<table style="border-collapse: collapse;">
				<?php foreach($messages as $message): ?>
					<tr>
						<td class="date"><a id="message<?php echo $message['id'] . '_' . $message['epoch'] ?>"></a><a href="?limit=<?php echo $limit ?>&amp;id=<?php echo $message['id'] . '&amp;epoch=' . $message['epoch'] ?>"><?php echo $message['date'] ?></a></td>
						<td class="user"><a class="<?php echo $message['color'] ?>" href="<?php echo $message['user_link'] ?>"><?php echo $message['user_name'] ?></a></td>
						<td class="message">
							<?php echo $message['message'] ?>
							<?php if(count($message['revisions']) > 0): ?>
								<a class="revisions" href="#" id="revisions_link_<?php echo $message['id'] . '_' . $message['epoch'] ?>">(<?php echo count($message['revisions']) ?> change<?php if(count($message['revisions']) > 1): ?>s<?php endif; ?>)</a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php foreach($messages as $message): ?>
				<?php if(count($message['revisions']) > 0): ?>
					<div id="revisions_<?php echo $message['id'] . '_' . $message['epoch'] ?>" style="display: none;">
						Change log:
						<ol>
							<?php foreach($message['revisions'] as $revision): ?>
								<li>
									<?php echo $revision['date'] ?>
									<span class="<?php echo $revision['color'] ?>"><?php echo $revision['user_name'] ?></span>
									<?php echo $revision['text'] ?>
								</li>
							<?php endforeach; ?>
							<li>
								<?php echo $message['date'] ?>
								<span class="<?php echo $message['color'] ?>"><?php echo $message['user_name'] ?></span>
								<?php echo $message['message'] ?> (<em>current</em>)
							</li>
						</ol>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
<?php if(!$ajax): ?>
		</div>
		<div style="padding-top: 15px; padding-left: 5px; white-space: nowrap;">
			Page <?php echo $page; ?> of <span class="page_count"><?php echo $page_count; ?></span> &ndash; <a href="<?php echo $first_link ?>">First</a> <a href="<?php echo $previous_link ?>">Previous</a> <a href="<?php echo $next_link ?>" class="next_link">Next</a> <a href="<?php echo $last_link ?>" class="last_link">Last</a>
		</div>
	</div>
	<hr />
	<p>
		<a href="http://validator.w3.org/check?uri=referer"><img src="images/xhtml.png" alt="Valid XHTML 1.1" height="31" width="88" /></a>
	</p>
</body>
</html>
<?php endif; /* if(!$ajax) */ ?>

