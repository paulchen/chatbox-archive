<?php
require_once(dirname(__FILE__) . '/../lib/common.php');
require_once(dirname(__FILE__) . '/../lib/ego.php');

function build_link_from_request() {
	$keys = func_get_args();
	$link_parts = '';
	foreach($keys as $key) {
		if(isset($_REQUEST[$key])) {
			$link_parts .= "&amp;$key=" . urlencode($_REQUEST[$key]);
		}
	}
	return $link_parts;
}

function overview_redirect() {
	header('Location: ' . basename($_SERVER['SCRIPT_FILENAME']));
	die();
}

function add_user_link(&$row) {
	// TODO simplify this
	$link_parts = build_link_from_request('day', 'month', 'year', 'hour', 'smiley', 'period', 'word');

	$row[0]['name'] = '<a href="details.php?user=' . urlencode($row[0]['name']) . $link_parts . '">' . $row[0]['name'] . '</a>';
}

function messages_per_hour(&$row) {
	$link_parts = build_link_from_request('day', 'month', 'year', 'user', 'smiley', 'period', 'word');

	$row[0]['hour'] = '<a href="details.php?hour=' . $row[0]['hour'] . $link_parts . '">' . $row[0]['hour'] . '</a>';
	spammer_smiley($row);
}

function messages_per_month(&$row) {
	$link_parts = build_link_from_request('user', 'hour', 'smiley', 'period', 'word');

	$parts = explode('-', $row[0]['month']);
	$year = $parts[0];
	$month = $parts[1];
	$row[0]['month'] = "<a href=\"details.php?month=$month&amp;year=$year$link_parts\">" . $row[0]['month'] . '</a>';
	spammer_smiley($row);
}

function messages_per_year(&$row) {
	$link_parts = build_link_from_request('user', 'hour', 'smiley', 'period', 'word');

	$row[0]['year'] = "<a href=\"details.php?year=" . $row[0]['year'] . "$link_parts\">" . $row[0]['year'] . '</a>';
	spammer_smiley($row);
}

function init_ego(&$user_egos, $id) {
	if(!isset($user_egos[$id])) {
		$user_egos[$id] = 0;
	}
}

function total_words($data) {
	$data = $data[0];

	usort($data, function($a, $b) {
		if($a['total_words'] == $b['total_words']) {
			if($a['shouts'] == $b['shouts']) {
				if($a['name'] < $b['name']) {
					return -1;
				}
				return 1;
			}
			if($a['shouts'] < $b['shouts']) {
				return 1;
			}
			return -1;
		}
		if($a['total_words'] < $b['total_words']) {
			return 1;
		}
		return -1;
	});

	return $data;
}

function top_spammers_total($data) {
	global $total_days;

	$total_shouts = 0;
	$total_smilies = 0;
	$total_words = 0;
	foreach($data[0] as $row) {
		$total_shouts += $row['shouts'];
		$total_smilies += $row['smilies'];
		$total_words += $row['total_words'];
	}

	return array('',
		'Total',
		$total_shouts,
		sprintf('%.4f', round($total_shouts/$total_days, 4)),
		'100.0000 %',
		$total_smilies,
		sprintf('%.4f', round($total_smilies/$total_shouts, 4)),
		'',
		'',
		$total_words,
		sprintf('%.4f', round($total_words/$total_shouts, 4))
	);
}

$main_page = false;
if(!isset($_REQUEST['user']) && !isset($_REQUEST['year']) && !isset($_REQUEST['hour']) && !isset($_REQUEST['smiley']) && !isset($_REQUEST['period']) && !isset($_REQUEST['word'])) {
	$main_page = true;
}
if(isset($_REQUEST['day']) && !isset($_REQUEST['month'])) {
	overview_redirect();
}
if(isset($_REQUEST['month']) && !isset($_REQUEST['year'])) {
	overview_redirect();
}

foreach(array('day', 'month', 'year', 'hour', 'smiley') as $item) {
	if(isset($_REQUEST[$item]) && !preg_match('/^[0-9]+$/', $_REQUEST[$item])) {
		overview_redirect();
	}
}
if(isset($_REQUEST['user'])) {
	$user = $_REQUEST['user'];

	$user_data = db_query('SELECT id FROM users WHERE name = ?', array($user));
	if(count($user_data) != 1) {
		overview_redirect();
	}
	$user_id = $user_data[0]['id'];
}
if(isset($_REQUEST['smiley'])) {
	$smiley_id = $_REQUEST['smiley'];

	$smiley_data = db_query('SELECT filename FROM smilies WHERE id = ?', array($smiley_id));
	if(count($smiley_data) != 1) {
		overview_redirect();
	}
	$smiley_filename = $smiley_data[0]['filename'];
}
if(isset($_REQUEST['word'])) {
	$word = $_REQUEST['word'];

	$word_data = db_query('SELECT id FROM words WHERE word = ?', array($word));
	if(count($word_data) != 1) {
		die();
		overview_redirect();
	}
	$word_id = $word_data[0]['id'];
}
if(isset($_REQUEST['hour'])) {
	$hour = $_REQUEST['hour'];
}

$filter_parts = array('deleted=0');
$params = array();
$what_parts = array();

if(isset($_REQUEST['hour'])) {
	$filter_parts[] = "lpad(cast(\"hour\" % 24 as text), 2, '0') = ?";
	$params[] = $hour;
	$what_parts[] = "hour $hour";
}
if(isset($_REQUEST['year'])) {
	$filter_parts[] = 'year = ?';
	$params[] = $_REQUEST['year'];
	$what_parts[] = $_REQUEST['year'];
}
if(isset($_REQUEST['month'])) {
	$filter_parts[] = 'month = ?';
	$params[] = $_REQUEST['month'];
	array_pop($what_parts);
	$what_parts[] = $_REQUEST['year'] . '-' . $_REQUEST['month'];
}
if(isset($_REQUEST['day'])) {
	$filter_parts[] = 'day = ?';
	$params[] = $_REQUEST['day'];
	array_pop($what_parts);
	$what_parts[] = $_REQUEST['year'] . '-' . $_REQUEST['month'] . '-' . $_REQUEST['day'];
}
if(isset($_REQUEST['user'])) {
	$filter_parts[] = '"user" = ?';
	$params[] = $user_id;
	$what_parts[] = $user;
}
if(isset($_REQUEST['smiley'])) {
	$filter_parts[] = "(s.id, s.epoch) in (select shout_id, shout_epoch from shout_smilies where smiley = ?)";
	$params[] = $smiley_id;
	$what_parts[] = "smiley <img src=\"images/smilies/$smiley_filename\" alt=\"\" />";
}
if(isset($_REQUEST['word'])) {
	$filter_parts[] = "(s.id, s.epoch) in (select shout_id, shout_epoch from shout_words where word = ?)";
	$params[] = $word_id;
	$what_parts[] = "word \"" . htmlentities($_REQUEST['word'], ENT_QUOTES, 'UTF-8') . "\"";
}
if(isset($_REQUEST['period'])) {
	$data = db_query('SELECT query, title FROM periods WHERE name = ?', array($_REQUEST['period']));
	if(count($data) != 1) {
		overview_redirect();
	}

	$filter_parts[] = $data[0]['query'];
	$what_parts[] = $data[0]['title'];
}

$filter = implode(' AND ', $filter_parts);
$what = implode(', ', $what_parts);

$data = db_query("SELECT GREATEST(CEIL((UNIX_TIMESTAMP(MAX(date))-UNIX_TIMESTAMP(MIN(date)))/86400.0), 1) days FROM shouts s WHERE $filter", $params);
$total_days = $data[0]['days'];

$queries = array();
$queries[] = array(
		'title' => 'Top spammers',
		'query' => "with smileycount as (
				select s.user, sm.smiley, sum(sm.count) count from shouts s join shout_smilies sm on (s.id=sm.shout_id and s.epoch=sm.shout_epoch) where $filter group by s.user, sm.smiley
			), wordcount as (
				select s.user, sw.word, sum(sw.count) count from shouts s join shout_words sw on (s.id=sw.shout_id and s.epoch=sw.shout_epoch) where $filter group by s.user, sw.word
			), shout_data as (
				select u.id, u.name, count(distinct s.id) shouts, unix_timestamp(min(date)) first_shout, unix_timestamp(max(date)) last_shout, count(ss.smiley) smilies
					from users u join shouts s on (u.id=s.user)
					left join shout_smilies ss on (s.id = ss.shout_id and s.epoch = ss.shout_epoch)
					where $filter
					group by u.name, u.id
			)
				select d.name, d.shouts,
					round(cast(d.shouts/greatest(ceil((d.last_shout-d.first_shout)/86400.0), 1) as numeric), 4) as average_shouts_per_day,
					concat(round(100*d.shouts/(select sum(shouts) from shout_data), 4), ' %') percentage,
					d.smilies, round(cast(d.smilies/cast(d.shouts as float) as numeric), 4),
					concat(c.smiley, '$$', sm.filename, '$$', c.count) smiley_info, concat(g.word, '$$', w.word, '$$', g.count) word_info,
					i.count total_words,
					round(i.count/d.shouts, 4) avg_words
				from
					shout_data d
					left join
					(
						(select a.user, max(a.count) max
							from smileycount a
							group by a.user) b
						left join smileycount c
						on (b.user = c.user and b.max = c.count)) on (d.id = b.user)
					left join smilies sm on (c.smiley = sm.id)
					left join
					(
						(select e.user, max(e.count) max
							from wordcount e
							group by e.user) f
						left join wordcount g
						on (f.user = g.user and f.max = g.count)) on (d.id = f.user)
					left join words w on (g.word = w.id)
					left join
					(
						select h.user, sum(h.count) count
							from wordcount h
							group by h.user) i on (d.id = i.user)
				order by d.shouts desc, average_shouts_per_day asc, d.name asc",
		'params' => array_merge($params, $params, $params),
		'processing_function' => array('add_user_link', 'smiley_column', 'word_column'),
		'processing_function_all' => array('duplicates0', 'insert_position', 'ex_aequo2'),
		'columns' => array('Position', 'Username', 'Messages', 'Avg msgs/day', '% of all msgs', 'Total smilies', 'Avg smilies/msg', 'Most popular smiley', 'Most popular word', 'Total words', 'avg words/msg'),
		'column_styles' => array('right', 'left', 'right', 'right', 'right', 'right', 'right', 'left', 'left', 'right', 'right'),
		'total' => 'top_spammers_total',
		'derived_queries' => array(
			array(
				'title' => 'Top spammers, ordered by messages per day',
				'transformation_function' => 'top_spammers',
				'processing_function' => array('add_user_link', 'smiley_column', 'word_column'),
				'processing_function_all' => array('duplicates0', 'ex_aequo3'),
				'columns' => array('Position', 'Username', 'Messages', 'Avg msgs/day', '% of all msgs', 'Total smilies', 'Avg smilies/msg', 'Most popular smiley', 'Most popular word', 'Total words', 'avg words/msg'),
				'column_styles' => array('right', 'left', 'right', 'right', 'right', 'right', 'right', 'left', 'left', 'right', 'right'),
				'total' => 'top_spammers_total',
			),
			array(
				'title' => 'Top spammers, ordered by total words',
				'transformation_function' => 'total_words',
				'processing_function' => array('add_user_link', 'smiley_column', 'word_column'),
				'processing_function_all' => array('duplicates0', 'insert_position', 'ex_aequo9'),
				'columns' => array('Position', 'Username', 'Messages', 'Avg msgs/day', '% of all msgs', 'Total smilies', 'Avg smilies/msg', 'Most popular smiley', 'Most popular word', 'Total words', 'avg words/msg'),
				'column_styles' => array('right', 'left', 'right', 'right', 'right', 'right', 'right', 'left', 'left', 'right', 'right'),
				'total' => 'top_spammers_total',
			),
		),
	);
$queries[] = array(
		'title' => 'Messages per hour',
		'query' => "with smileycount as (
				select s.hour, sm.smiley, sum(sm.count) count from shouts s join shout_smilies sm on (s.id=sm.shout_id and s.epoch=sm.shout_epoch) where $filter group by s.hour, sm.smiley
			), wordcount as (
				select s.hour, sw.word, sum(sw.count) count from shouts s join shout_words sw on (s.id=sw.shout_id and s.epoch=sw.shout_epoch) where $filter group by s.hour, sw.word
			), hours as (
				select \"user\", hour, count(*) count from shouts s where $filter group by \"user\", hour
			)
				select lpad(cast(h.hour as text), 2, '0') \"hour\", coalesce(j.count, 0) shouts, concat(c.user, '$$', u.name, '$$', c.count) top_spammer,
					concat(f.smiley, '$$', sm.filename, '$$', f.count) popular_smiley, concat(i.word, '$$', w.word, '$$', i.count) popular_word
				from hours_of_day h
					left join
					(select hour, count(s.id) count from shouts s where $filter group by hour) j on (h.hour=j.hour)
					left join
					(
						(select hour, max(count) max from hours a group by hour) b
						left join hours c
						on (b.hour=c.hour and b.max=c.count)
					) on (j.hour=b.hour)
					left join users u on (c.user=u.id)
					left join
					(
						(select e.hour, max(e.count) max
							from smileycount e
							group by e.hour) d
						left join smileycount f
						on (d.hour = f.hour and d.max = f.count)
					) on (j.hour=d.hour)
					left join smilies sm on (f.smiley = sm.id)
					left join
					(
						(select h.hour, max(h.count) max
							from wordcount h
							group by h.hour) g
						left join wordcount i
						on (g.hour = i.hour and g.max = i.count)
					) on (j.hour=g.hour)
					left join words w on (i.word = w.id)
					order by h.hour asc",
		'params' => array_merge($params, $params, $params, $params),
		'processing_function' => 'messages_per_hour',
		'processing_function_all' => 'duplicates0',
		'columns' => array('Hour', 'Messages', 'Top spammer', 'Most popular smiley', 'Most popular word'),
		'column_styles' => array('left', 'right', 'left', 'left', 'left'),
		'derived_queries' => array(
			array(
				'title' => 'Busiest hours',
				'transformation_function' => 'busiest_hours',
				'processing_function' => 'messages_per_hour',
				'processing_function_all' => array('duplicates0', 'insert_position', 'ex_aequo2'),
				'columns' => array('Position', 'Hour', 'Messages', 'Top spammer', 'Most popular smiley', 'Most popular word'),
				'column_styles' => array('right', 'right', 'right', 'left', 'left', 'left'),
			),
		),
	);
$queries[] = array(
		'title' => 'Busiest days',
		'query' => "with smileycount as (
				select s.day, s.month, s.year, sm.smiley, sum(sm.count) count from shouts s join shout_smilies sm on (s.id=sm.shout_id and s.epoch=sm.shout_epoch) where $filter group by s.day, s.month, s.year, sm.smiley
			), wordcount as (
				select s.day, s.month, s.year, sw.word, sum(sw.count) count from shouts s join shout_words sw on (s.id=sw.shout_id and s.epoch=sw.shout_epoch) where $filter group by s.day, s.month, s.year, sw.word
			), hours as (
				select \"user\", day, month, year, count(*) count from shouts s where $filter group by \"user\", day, month, year
			)
				select concat(cast(j.year as text), '-', lpad(cast(j.month as text), 2, '0'), '-', lpad(cast(j.day as text), 2, '0')) \"day\", j.count shouts, concat(c.user, '$$', u.name, '$$', c.count) top_spammer,
                                        concat(f.smiley, '$$', sm.filename, '$$', f.count) popular_smiley, concat(i.word, '$$', w.word, '$$', i.count) popular_word
                                from (select day, month, year, count(s.id) count from shouts s where $filter group by day, month, year order by count desc limit 10) j
                                        left join
                                        (
						(select day, month, year, max(count) max from hours a group by day, month, year) b
                                                left join hours c
                                                on (b.day=c.day and b.month=c.month and b.year=c.year and b.max=c.count)
                                        ) on (j.day=b.day and j.month=b.month and j.year=b.year)
                                        left join users u on (c.user=u.id)
                                        left join
                                        (
						(select e.day, e.month, e.year, max(e.count) max
							from smileycount e
                                                        group by e.day, e.month, e.year) d
                                                left join smileycount f
                                                on (d.day = f.day and d.month = f.month and d.year = f.year and d.max = f.count)
                                        ) on (j.day=d.day and j.month=d.month and j.year=d.year)
                                        left join smilies sm on (f.smiley = sm.id)
                                        left join
                                        (
						(select h.day, h.month, h.year, max(h.count) max
							from wordcount h
                                                        group by h.day, h.month, h.year) g
                                                left join wordcount i
                                                on (g.day = i.day and g.month = i.month and g.year = i.year and g.max = i.count)
                                        ) on (j.day=g.day and j.month=g.month and j.year=g.year)
                                        left join words w on (i.word = w.id)
                                        order by j.count desc, j.year asc, j.month asc, j.day asc",
		'params' => array_merge($params, $params, $params, $params),
		'processing_function' => function(&$row) {
				$parts = explode('-', $row[0]['day']);
				$year = $parts[0];
				$month = $parts[1];
				$day = $parts[2];
				$row[0]['day'] = "<a href=\"details.php?day=$day&amp;month=$month&amp;year=$year\">" . $row[0]['day'] . '</a>';
				spammer_smiley($row);
			},
		'processing_function_all' => array('duplicates0', 'insert_position'),
		'columns' => array('Position', 'Day', 'Messages', 'Top spammer', 'Most popular smiley', 'Most popular word'),
		'column_styles' => array('right', 'left', 'right', 'left', 'left', 'left'),
	);
if(!isset($_REQUEST['day'])) {
	$queries[] = array(
			'title' => 'Messages per month',
			'query' => "with smileycount as (
				select s.month, s.year, sm.smiley, sum(sm.count) count from shouts s join shout_smilies sm on (s.id=sm.shout_id and s.epoch=sm.shout_epoch) where $filter group by s.month, s.year, sm.smiley
			), wordcount as (
				select s.month, s.year, sw.word, sum(sw.count) count from shouts s join shout_words sw on (s.id=sw.shout_id and s.epoch=sw.shout_epoch) where $filter group by s.month, s.year, sw.word
			), hours as (
				select \"user\", month, year, count(*) count from shouts s where $filter group by \"user\", month, year
			)
					select concat(cast(j.year as text), '-', lpad(cast(j.month as text), 2, '0')) \"month\", j.count shouts, concat(c.user, '$$', u.name, '$$', c.count) top_spammer,
						concat(f.smiley, '$$', sm.filename, '$$', f.count) popular_smiley, concat(i.word, '$$', w.word, '$$', i.count) popular_word
					from (select month, year, count(s.id) count from shouts s where $filter group by month, year) j
						left join
						(
							(select month, year, max(count) max from hours a group by month, year) b
							left join hours c
							on (b.month=c.month and b.year=c.year and b.max=c.count)
						) on (j.month=b.month and j.year=b.year)
						left join users u on (c.user=u.id)
						left join
						(
							(select e.month, e.year, max(e.count) max
								from smileycount e
								group by e.month, e.year) d
							left join smileycount f
							on (d.month = f.month and d.year = f.year and d.max = f.count)
						) on (j.month=d.month and j.year=d.year)
						left join smilies sm on (f.smiley = sm.id)
						left join
						(
							(select h.month, h.year, max(h.count) max
								from wordcount h
								group by h.month, h.year) g
							left join wordcount i
							on (g.month = i.month and g.year = i.year and g.max = i.count)
						) on (j.month=g.month and j.year=g.year)
						left join words w on (i.word = w.id)
						order by j.year asc, j.month asc",
			'params' => array_merge($params, $params, $params, $params),
			'processing_function' => 'messages_per_month',
			'processing_function_all' => 'duplicates0',
			'columns' => array('Month', 'Messages', 'Top spammer', 'Most popular smiley', 'Most popular word'),
			'column_styles' => array('left', 'right', 'left', 'left', 'left'),
			'derived_queries' => array(
				array(
					'title' => 'Messages per month, ordered by number of messages',
					'transformation_function' => 'busiest_time',
					'processing_function' => 'messages_per_month',
					'processing_function_all' => array('duplicates1', 'ex_aequo2'),
					'columns' => array('Position', 'Month', 'Messages', 'Top spammer', 'Most popular smiley', 'Most popular word'),
					'column_styles' => array('right', 'left', 'right', 'left', 'left', 'left'),
				),
			),
		);
}
if(!isset($_REQUEST['month'])) {
	$queries[] = array(
			'title' => 'Messages per year',
			'query' => "with smileycount as (
				select s.year, sm.smiley, sum(sm.count) count from shouts s join shout_smilies sm on (s.id=sm.shout_id and s.epoch=sm.shout_epoch) where $filter group by s.year, sm.smiley
			), wordcount as (
				select s.year, sw.word, sum(sw.count) count from shouts s join shout_words sw on (s.id=sw.shout_id and s.epoch=sw.shout_epoch) where $filter group by s.year, sw.word
			), hours as (
				select \"user\", year, count(*) count from shouts s where $filter group by \"user\", year
			)
					select j.year, j.count shouts, concat(c.user, '$$', u.name, '$$', c.count) top_spammer,
						concat(f.smiley, '$$', sm.filename, '$$', f.count) popular_smiley, concat(i.word, '$$', w.word, '$$', i.count) popular_word
					from (select year, count(s.id) count from shouts s where $filter group by year) j
						left join
						(
							(select year, max(count) max from hours a group by year) b
							left join hours c
							on (b.year=c.year and b.max=c.count)
						) on (j.year=b.year)
						left join users u on (c.user=u.id)
						left join
						(
							(select e.year, max(e.count) max
								from smileycount e
								group by e.year) d
							left join smileycount f
							on (d.year = f.year and d.max = f.count)
						) on (j.year=d.year)
						left join smilies sm on (f.smiley = sm.id)
						left join
						(
							(select h.year, max(h.count) max
								from wordcount h
								group by h.year) g
							left join wordcount i
							on (g.year = i.year and g.max = i.count)
						) on (j.year=g.year)
						left join words w on (i.word = w.id)
						order by j.year asc",
			'params' => array_merge($params, $params, $params, $params),
			'processing_function' => 'messages_per_year',
			'processing_function_all' => 'duplicates0',
			'columns' => array('Year', 'Messages', 'Top spammer', 'Most popular smiley', 'Most popular word'),
			'column_styles' => array('left', 'right', 'left', 'left', 'left'),
			'derived_queries' => array(
				array(
					'title' => 'Messages per year, ordered by number of messages',
					'transformation_function' => 'busiest_time',
					'processing_function' => 'messages_per_year',
					'processing_function_all' => array('duplicates0', 'ex_aequo2'),
					'columns' => array('Position', 'Year', 'Messages', 'Top spammer', 'Most popular smiley', 'Most popular word'),
					'column_styles' => array('right', 'left', 'right', 'left', 'left', 'left'),
				),
			),
		);
}
$queries[] = array(
		'title' => 'Smiley usage',
		'query' => "with smileycount as (
				select s.user, ss.smiley, sum(count) count
					from shouts s join shout_smilies ss on (s.id=ss.shout_id and s.epoch=ss.shout_epoch)
					where $filter 
					group by s.user, ss.smiley
			)
				select sm.filename, d.count, concat(u.id, '$$', u.name, '$$', c.count) top
				from
					(select smiley, coalesce(sum(count), 0) count
						from smileycount
						group by smiley) d
				left join
					(
						(select a.smiley, max(count) max
							from smileycount a
							group by a.smiley) b
					left join smileycount c
					on (b.smiley=c.smiley and b.max=c.count))
				on (d.smiley=b.smiley)
				left join users u on (c.user=u.id)
				left join smilies sm on (d.smiley=sm.id)
				order by d.count desc, sm.filename asc",
		'params' => $params,
		'processing_function' => function(&$row) {
				global $smilies;

				if(!isset($smilies)) {
					$query = 'SELECT id, filename FROM smilies';
					$smilies = db_query($query, array());
				}

				foreach($smilies as $smiley) {
					if($smiley['filename'] == $row[0]['filename']) {
						$smiley_id = $smiley['id'];
						break;
					}
				}

				$row[0]['filename'] = '<a href="details.php?smiley=' . $smiley_id . '"><img src="images/smilies/' . $row[0]['filename'] . '" alt="" /></a>';

				$top = explode('$$', $row[0]['top']);
				$user_id = $top[0];
				$username = $top[1];
				$frequency = $top[2];
				$link = 'details.php?user=' . urlencode($username);
				$row[0]['top'] = "<a href=\"$link\">$username</a> (${frequency}x)";
			},
		'processing_function_all' => array('duplicates0', 'insert_position'),
		'columns' => array('Position', 'Smiley', 'Occurrences', 'Top user'),
		'column_styles' => array('right', 'right', 'right', 'left'),
	);
$queries[] = array(
		'title' => 'Word usage (top 100)',
		'query' => "with wordcount as (
			select s.user, sw.word, sum(count) count
				from shouts s join shout_words sw on (s.id=sw.shout_id and s.epoch=sw.shout_epoch)
				where $filter
				group by s.user, sw.word
		)
				select w.word, d.count, concat(u.id, '$$', u.name, '$$', c.count) top
				from 
					(select word, coalesce(sum(count), 0) count
						from wordcount
						group by word
						order by count desc
						limit 100) d
				left join
					(
						(select a.word, max(count) max
							from wordcount a
							group by a.word) b
					left join wordcount c
					on (b.word=c.word and b.max=c.count))
				on (d.word=b.word)
				left join users u on (c.user=u.id)
				join words w on (d.word=w.id)
				order by d.count desc, w.word asc",
		'params' => $params,
		'processing_function' => array(function(&$row) {
				$row[0]['word'] = '<a href="details.php?word=' . urlencode($row[0]['word']) . '">' . $row[0]['word'] . '</a>';

				$top = explode('$$', $row[0]['top']);
				$user_id = $top[0];
				$username = $top[1];
				$frequency = $top[2];
				$link = 'details.php?user=' . urlencode($username);
				$row[0]['top'] = "<a href=\"$link\">$username</a> (${frequency}x)";
			}),
		'processing_function_all' => array('duplicates0', 'insert_position'),
		'columns' => array('Position', 'Word', 'Occurrences', 'Top user'),
		'column_styles' => array('right', 'left', 'right', 'left'),
	);
if(!isset($_REQUEST['smiley']) && !isset($_REQUEST['word']) && !isset($_REQUEST['user'])) {
	$queries[] = array(
			'title' => "Ego points",
			'query' => "SELECT u.id AS id, s.message AS message
				FROM shouts s
					JOIN users u ON (s.user = u.id)
				WHERE s.deleted = 0
					AND (s.message LIKE '%ego%' OR s.message LIKE '%/hail.gif%' OR s.message LIKE '%/multihail.gif%' OR s.message LIKE '%/antihail.png%')
					AND $filter
				ORDER BY s.id ASC",
			'params' => $params,
			'processing_function_all' => array(function(&$data) {
					$result = calculate_ego($data[0]);
					$user_egos = $result['user_egos'];

					$datax = db_query('SELECT u.id AS id, u.name AS name, c.color AS color
							FROM users u
								JOIN user_categories c ON (u.category = c.id)');
					$users = array();
					foreach($datax as $row) {
						if($row['color'] == '-') {
							$row['color'] = 'user';
						}
						$users[$row['id']] = $row;
					}

					while(count($data[0]) > 0) {
						array_shift($data[0]);
					}
					$pos = 0;
					foreach($user_egos as $id => $ego) {
						$data[0][] = array(
							++$pos,
							'<a href="./?text=ego&amp;user=' . urlencode($users[$id]['name']) . '&amp;limit=100&amp;page=1&amp;date=&amp;refresh=on" class="' . $users[$id]['color'] . '">' . $users[$id]['name'] . '</a>',
							$ego
						);
					}
				}),
			'columns' => array('Position', 'User', 'Ego'),
			'column_styles' => array('right', 'left', 'right'),
			'cached' => false,
			'note' => 'For details about how ego points are calculated, please refer to the <a href="ego.php">global list of ego points</a>.',
		);
}
/*
$queries[] = array(
		'title' => '',
		'query' => "",
		'columns' => array(),
		'column_styles' => array(),
	);
 */
$query_total = array(
		'query' => "SELECT COUNT(*) shouts FROM shouts s WHERE $filter",
		'params' => $params,
	);

if($main_page) {
	/* cached data from misc/update_stats.php */
	$extra_stats = $memcached->get("${memcached_prefix}_stats_min_max");
	$page_title = 'Spam overview';
	$backlink = array(
		'url' => 'index.php',
		'text' => 'Chatbox archive',
	);
}
else {
	$page_title = "Spam overview: $what";
	$backlink = array(
			'url' => 'details.php',
			'text' => 'Spam overview',
		);
}

require_once(dirname(__FILE__) . '/../lib/stats.php');

log_data();

