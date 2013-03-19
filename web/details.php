<?php
require_once(dirname(__FILE__) . '/../lib/common.php');

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
	header('Location: overview.php');
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

	$parts = explode('-', $row[0]['monthx']);
	$year = $parts[0];
	$month = $parts[1];
	$row[0]['monthx'] = "<a href=\"details.php?month=$month&amp;year=$year$link_parts\">" . $row[0]['monthx'] . '</a>';
	spammer_smiley($row);
}

function messages_per_year(&$row) {
	$link_parts = build_link_from_request('user', 'hour', 'smiley', 'period', 'word');

	$row[0]['yearx'] = "<a href=\"details.php?year=" . $row[0]['yearx'] . "$link_parts\">" . $row[0]['yearx'] . '</a>';
	spammer_smiley($row);
}

if(!isset($_REQUEST['user']) && !isset($_REQUEST['year']) && !isset($_REQUEST['hour']) && !isset($_REQUEST['smiley']) && !isset($_REQUEST['period']) && !isset($_REQUEST['word'])) {
	overview_redirect();
}
if(isset($_REQUEST['day']) && !isset($_REQUEST['month'])) {
	overview_redirect();
}
if(isset($_REQUEST['month']) && !isset($_REQUEST['year'])) {
	overview_redirect();
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

$filter_parts = array();
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
	// TODO improve this
	$last_archive_id = 229152;
	$last_archive_epoch = 1;

	$data = db_query('SELECT UNIX_TIMESTAMP(date) date FROM shouts WHERE (epoch = ? AND id >= ?) OR (epoch > ?) ORDER BY epoch ASC, id ASC LIMIT 1', array($last_archive_epoch, $last_archive_id, $last_archive_epoch));
	$date_string = date('Y-m-d', $data[0]['date']);

	$filter_parts[] = '(s.epoch = ? AND s.id >= ?) OR (epoch > ?)';
	$params[] = $last_archive_epoch;
	$params[] = $last_archive_id;
	$params[] = $last_archive_epoch;
	$what_parts[] = "since $date_string";
}

$filter = implode(' AND ', $filter_parts);
$what = implode(', ', $what_parts);

$queries = array();
/*
$queries[] = array(
		'title' => 'Top spammers',
		'query' => "select d.name, d.shouts,
					round(cast(d.shouts/greatest(ceil((d.last_shout-d.first_shout)/86400.0), 1) as numeric), 4) as average_shouts_per_day,
					d.smilies, round(cast(d.smilies/cast(d.shouts as float) as numeric), 4),
					concat(c.smiley, '$$', sm.filename, '$$', c.count) smiley_info, concat(g.word, '$$', w.word, '$$', g.count) word_info
				from
					(select u.id, u.name, count(distinct s.id) shouts, unix_timestamp(min(date)) first_shout, unix_timestamp(max(date)) last_shout, count(ss.smiley) smilies
						from users u join shouts s on (u.id=s.user)
						left join shout_smilies ss on (s.id = ss.shout_id and s.epoch = ss.shout_epoch)
						where deleted=0 and $filter
						group by u.name, u.id) d
					left join
					(
						(select a.user, max(a.count) max
							from (select s.user, sum(sm.count) count from shouts s join shout_smilies sm on (s.id=sm.shout_id and s.epoch=sm.shout_epoch) where deleted=0 and $filter group by s.user, sm.smiley) a
							group by a.user) b
						left join
						(select s.user, sm.smiley, sum(sm.count) count from shouts s join shout_smilies sm on (s.id=sm.shout_id and s.epoch=sm.shout_epoch) where deleted=0 and $filter group by s.user, sm.smiley) c
						on (b.user = c.user and b.max = c.count)) on (d.id = b.user)
					left join smilies sm on (c.smiley = sm.id)
					left join
					(
						(select e.user, max(e.count) max
							from (select s.user, sum(sw.count) count from shouts s join shout_words sw on (s.id=sw.shout_id and s.epoch=sw.shout_epoch) where deleted=0 and $filter group by s.user, sw.word) e
							group by e.user) f
						left join
						(select s.user, sw.word, sum(sw.count) count from shouts s join shout_words sw on (s.id=sw.shout_id and s.epoch=sw.shout_epoch) where deleted=0 and $filter group by s.user, sw.word) g
						on (f.user = g.user and f.max = g.count)) on (d.id = f.user)
					left join words w on (g.word = w.id)
				order by d.shouts desc, average_shouts_per_day asc, d.name asc",
		'params' => array_merge($params, $params, $params, $params, $params),
		'processing_function' => array('add_user_link', 'smiley_column', 'word_column'),
		'processing_function_all' => array('duplicates0', 'insert_position', 'ex_aequo2'),
		'columns' => array('Position', 'Username', 'Messages', 'Avg msgs/day', 'Total smilies', 'Avg smilies/msg', 'Most popular smiley', 'Most popular word'),
		'column_styles' => array('right', 'left', 'right', 'right', 'right', 'right', 'left', 'left'),
		'derived_queries' => array(
			array(
				'title' => 'Top spammers, ordered by messages per day',
				'transformation_function' => 'top_spammers',
				'processing_function' => array('add_user_link', 'smiley_column', 'word_column'),
				'processing_function_all' => array('duplicates0', 'ex_aequo3'),
				'columns' => array('Position', 'Username', 'Messages', 'Avg msgs/day', 'Total smilies', 'Avg smilies/msg', 'Most popular smiley', 'Most popular word'),
				'column_styles' => array('right', 'left', 'right', 'right', 'right', 'right', 'left', 'left'),
			),
		),
	);
$queries[] = array(
		'title' => 'Messages per hour',
		'query' => "select lpad(cast(h.hour as text), 2, '0') \"hour\", j.count shouts, concat(c.user, '$$', u.name, '$$', c.count) top_spammer,
					concat(f.smiley, '$$', sm.filename, '$$', f.count) popular_smiley, concat(i.word, '$$', w.word, '$$', i.count) popular_word
				from hours_of_day h
					left join
					(select hour, count(s.id) count from shouts s where deleted=0 and $filter group by hour) j on (h.hour=j.hour)
					left join
					(
						(select hour, max(count) max from (select \"user\", hour, count(*) count from shouts s where deleted=0 and $filter group by \"user\", hour) a group by hour) b
						left join
						(select \"user\", hour, count(*) count from shouts s where deleted=0 and $filter group by \"user\", hour) c
						on (b.hour=c.hour and b.max=c.count)
					) on (j.hour=b.hour)
					left join users u on (c.user=u.id)
					left join
					(
						(select e.hour, max(e.count) max
							from (select s.hour, sum(sm.count) count from shouts s join shout_smilies sm on (s.id=sm.shout_id and s.epoch=sm.shout_epoch) where deleted=0 and $filter group by s.hour, sm.smiley) e
							group by e.hour) d
						left join
						(select s.hour, sm.smiley, sum(sm.count) count from shouts s join shout_smilies sm on (s.id=sm.shout_id and s.epoch=sm.shout_epoch) where deleted=0 and $filter group by s.hour, sm.smiley) f
						on (d.hour = f.hour and d.max = f.count)
					) on (j.hour=d.hour)
					left join smilies sm on (f.smiley = sm.id)
					left join
					(
						(select h.hour, max(h.count) max
							from (select s.hour, sum(sw.count) count from shouts s join shout_words sw on (s.id=sw.shout_id and s.epoch=sw.shout_epoch) where deleted=0 and $filter group by s.hour, sw.word) h
							group by h.hour) g
						left join
						(select s.hour, sw.word, sum(sw.count) count from shouts s join shout_words sw on (s.id=sw.shout_id and s.epoch=sw.shout_epoch) where deleted=0 and $filter group by s.hour, sw.word) i
						on (g.hour = i.hour and g.max = i.count)
					) on (j.hour=g.hour)
					left join words w on (i.word = w.id)
					order by h.hour asc",
		'params' => array_merge($params, $params, $params, $params, $params, $params, $params),
		'processing_function' => 'messages_per_hour',
		'processing_function_all' => 'duplicates0',
		'columns' => array('Hour', 'Messages', 'Top spammer', 'Most popular smiley', 'Most popular word'),
		'column_styles' => array('left', 'right', 'left', 'left', 'left'),
		'derived_queries' => array(
			array(
				'title' => 'Busiest hours',
				'transformation_function' => 'busiest_hours',
				'processing_function' => 'messages_per_hour',
				'processing_function_all' => 'duplicates0',
				'columns' => array('Hour', 'Messages', 'Top spammer', 'Most popular smiley', 'Most popular word'),
				'column_styles' => array('left', 'right', 'left', 'left', 'left'),
			),
		),
	);
$queries[] = array(
		'title' => 'Busiest days',
		'query' => "select concat(cast(j.year as text), '-', lpad(cast(j.month as text), 2, '0'), '-', lpad(cast(j.day as text), 2, '0')) \"day\", j.count shouts, concat(c.user, '$$', u.name, '$$', c.count) top_spammer,
                                        concat(f.smiley, '$$', sm.filename, '$$', f.count) popular_smiley, concat(i.word, '$$', w.word, '$$', i.count) popular_word
                                from (select day, month, year, count(s.id) count from shouts s where deleted=0 and $filter group by day, month, year order by count desc limit 10) j
                                        left join
                                        (
                                                (select day, month, year, max(count) max from (select \"user\", day, month, year, count(*) count from shouts s where deleted=0 and $filter group by \"user\", day, month, year) a group by day, month, year) b
                                                left join
                                                (select \"user\", day, month, year, count(*) count from shouts s where deleted=0 and $filter group by \"user\", day, month, year) c
                                                on (b.day=c.day and b.month=c.month and b.year=c.year and b.max=c.count)
                                        ) on (j.day=b.day and j.month=b.month and j.year=b.year)
                                        left join users u on (c.user=u.id)
                                        left join
                                        (
                                                (select e.day, e.month, e.year, max(e.count) max
                                                        from (select s.day, s.month, s.year, sum(sm.count) count from shouts s join shout_smilies sm on (s.id=sm.shout_id and s.epoch=sm.shout_epoch) where deleted=0 and $filter group by s.day, s.month, s.year, sm.smiley) e
                                                        group by e.day, e.month, e.year) d
                                                left join
                                                (select s.day, s.month, s.year, sm.smiley, sum(sm.count) count from shouts s join shout_smilies sm on (s.id=sm.shout_id and s.epoch=sm.shout_epoch) where deleted=0 and $filter group by s.day, s.month, s.year, sm.smiley) f
                                                on (d.day = f.day and d.month = f.month and d.year = f.year and d.max = f.count)
                                        ) on (j.day=d.day and j.month=d.month and j.year=d.year)
                                        left join smilies sm on (f.smiley = sm.id)
                                        left join
                                        (
                                                (select h.day, h.month, h.year, max(h.count) max
                                                        from (select s.day, s.month, s.year, sum(sw.count) count from shouts s join shout_words sw on (s.id=sw.shout_id and s.epoch=sw.shout_epoch) where deleted=0 and $filter group by s.day, s.month, s.year, sw.word) h
                                                        group by h.day, h.month, h.year) g
                                                left join
                                                (select s.day, s.month, s.year, sw.word, sum(sw.count) count from shouts s join shout_words sw on (s.id=sw.shout_id and s.epoch=sw.shout_epoch) where deleted=0 and $filter group by s.day, s.month, s.year, sw.word) i
                                                on (g.day = i.day and g.month = i.month and g.year = i.year and g.max = i.count)
                                        ) on (j.day=g.day and j.month=g.month and j.year=g.year)
                                        left join words w on (i.word = w.id)
                                        order by j.count desc, j.year asc, j.month asc, j.day asc",
		'params' => array_merge($params, $params, $params, $params, $params, $params, $params),
		'processing_function' => function(&$row) {
				$parts = explode('-', $row[0]['day']);
				$year = $parts[0];
				$month = $parts[1];
				$day = $parts[2];
				$row[0]['day'] = "<a href=\"details.php?day=$day&amp;month=$month&amp;year=$year\">" . $row[0]['day'] . '</a>';
				spammer_smiley($row);
			},
		'processing_function_all' => array('duplicates0', 'insert_position'),
		'columns' => array('Position', 'Day', 'Messages', 'Top spammer', 'Most popular smiley'),
		'column_styles' => array('right', 'left', 'right', 'left', 'left'),
	);
if(!isset($_REQUEST['day'])) {
	$queries[] = array(
			'title' => 'Messages per month',
			'query' => "select concat(cast(j.year as text), '-', lpad(cast(j.month as text), 2, '0')) \"month\", j.count shouts, concat(c.user, '$$', u.name, '$$', c.count) top_spammer,
						concat(f.smiley, '$$', sm.filename, '$$', f.count) popular_smiley, concat(i.word, '$$', w.word, '$$', i.count) popular_word
					from (select month, year, count(s.id) count from shouts s where deleted=0 and $filter group by month, year) j
						left join
						(
							(select month, year, max(count) max from (select \"user\", month, year, count(*) count from shouts s where deleted=0 and $filter group by \"user\", month, year) a group by month, year) b
							left join
							(select \"user\", month, year, count(*) count from shouts s where deleted=0 and $filter group by \"user\", month, year) c
							on (b.month=c.month and b.year=c.year and b.max=c.count)
						) on (j.month=b.month and j.year=b.year)
						left join users u on (c.user=u.id)
						left join
						(
							(select e.month, e.year, max(e.count) max
								from (select s.month, s.year, sum(sm.count) count from shouts s join shout_smilies sm on (s.id=sm.shout_id and s.epoch=sm.shout_epoch) where deleted=0 and $filter group by s.month, s.year, sm.smiley) e
								group by e.month, e.year) d
							left join
							(select s.month, s.year, sm.smiley, sum(sm.count) count from shouts s join shout_smilies sm on (s.id=sm.shout_id and s.epoch=sm.shout_epoch) where deleted=0 and $filter group by s.month, s.year, sm.smiley) f
							on (d.month = f.month and d.year = f.year and d.max = f.count)
						) on (j.month=d.month and j.year=d.year)
						left join smilies sm on (f.smiley = sm.id)
						left join
						(
							(select h.month, h.year, max(h.count) max
								from (select s.month, s.year, sum(sw.count) count from shouts s join shout_words sw on (s.id=sw.shout_id and s.epoch=sw.shout_epoch) where deleted=0 and $filter group by s.month, s.year, sw.word) h
								group by h.month, h.year) g
							left join
							(select s.month, s.year, sw.word, sum(sw.count) count from shouts s join shout_words sw on (s.id=sw.shout_id and s.epoch=sw.shout_epoch) where deleted=0 and $filter group by s.month, s.year, sw.word) i
							on (g.month = i.month and g.year = i.year and g.max = i.count)
						) on (j.month=g.month and j.year=g.year)
						left join words w on (i.word = w.id)
						order by j.year asc, j.month asc",
			'params' => array_merge($params, $params, $params, $params, $params, $params, $params),
			'processing_function' => 'messages_per_month',
			'processing_function_all' => 'duplicates0',
			'columns' => array('Month', 'Messages', 'Top spammer', 'Most popular smiley'),
			'column_styles' => array('left', 'right', 'left', 'left'),
			'derived_queries' => array(
				array(
					'title' => 'Messages per month, ordered by number of messages',
					'transformation_function' => 'busiest_time',
					'processing_function' => 'messages_per_month',
					'processing_function_all' => array('duplicates1', 'ex_aequo2'),
					'columns' => array('Position', 'Month', 'Messages', 'Top spammer', 'Most popular smiley'),
					'column_styles' => array('right', 'left', 'right', 'left', 'left'),
				),
			),
		);
}
if(!isset($_REQUEST['month'])) {
	$queries[] = array(
			'title' => 'Messages per year',
			'query' => "select j.year, j.count shouts, concat(c.user, '$$', u.name, '$$', c.count) top_spammer,
						concat(f.smiley, '$$', sm.filename, '$$', f.count) popular_smiley, concat(i.word, '$$', w.word, '$$', i.count) popular_word
					from (select year, count(s.id) count from shouts s where deleted=0 and $filter group by year) j
						left join
						(
							(select year, max(count) max from (select \"user\", year, count(*) count from shouts s where deleted=0 and $filter group by \"user\", year) a group by year) b
							left join
							(select \"user\", year, count(*) count from shouts s where deleted=0 and $filter group by \"user\", year) c
							on (b.year=c.year and b.max=c.count)
						) on (j.year=b.year)
						left join users u on (c.user=u.id)
						left join
						(
							(select e.year, max(e.count) max
								from (select s.year, sum(sm.count) count from shouts s join shout_smilies sm on (s.id=sm.shout_id and s.epoch=sm.shout_epoch) where deleted=0 and $filter group by s.year, sm.smiley) e
								group by e.year) d
							left join
							(select s.year, sm.smiley, sum(sm.count) count from shouts s join shout_smilies sm on (s.id=sm.shout_id and s.epoch=sm.shout_epoch) where deleted=0 and $filter group by s.year, sm.smiley) f
							on (d.year = f.year and d.max = f.count)
						) on (j.year=d.year)
						left join smilies sm on (f.smiley = sm.id)
						left join
						(
							(select h.year, max(h.count) max
								from (select s.year, sum(sw.count) count from shouts s join shout_words sw on (s.id=sw.shout_id and s.epoch=sw.shout_epoch) where deleted=0 and $filter group by s.year, sw.word) h
								group by h.year) g
							left join
							(select s.year, sw.word, sum(sw.count) count from shouts s join shout_words sw on (s.id=sw.shout_id and s.epoch=sw.shout_epoch) where deleted=0 and $filter group by s.year, sw.word) i
							on (g.year = i.year and g.max = i.count)
						) on (j.year=g.year)
						left join words w on (i.word = w.id)
						order by j.year asc",
			'params' => array_merge($params, $params, $params, $params, $params, $params, $params),
			'processing_function' => 'messages_per_year',
			'processing_function_all' => 'duplicates0',
			'columns' => array('Year', 'Messages', 'Top spammer', 'Most popular smiley'),
			'column_styles' => array('left', 'right', 'left', 'left'),
			'derived_queries' => array(
				array(
					'title' => 'Messages per year, ordered by number of messages',
					'transformation_function' => 'busiest_time',
					'processing_function' => 'messages_per_year',
					'processing_function_all' => array('duplicates0', 'ex_aequo2'),
					'columns' => array('Position', 'Year', 'Messages', 'Top spammer', 'Most popular smiley'),
					'column_styles' => array('right', 'left', 'right', 'left', 'left'),
				),
			),
		);
}
 */
$filter2 = str_replace(array('s.epoch', 's.id'), array('s2.epoch', 's2.id'), $filter);
$filter3 = str_replace(array('s.epoch', 's.id'), array('sh.epoch', 'sh.id'), $filter);
$queries[] = array(
		'title' => 'Smiley usage',
		'query' => "select sm.filename, d.count, concat(u.id, '$$', u.name, '$$', c.count) top
				from
					(select ss.smiley, coalesce(sum(ss.count), 0) count
						from shouts s join shout_smilies ss on (s.id=ss.shout_id and s.epoch=ss.shout_epoch)
						where deleted=0 and $filter
						group by ss.smiley) d
				left join
					(
						(select a.smiley, max(count) max
							from
								(select s.user, ss.smiley, sum(count) count
									from shouts s join shout_smilies ss on (s.id=ss.shout_id and s.epoch=ss.shout_epoch)
									where s.deleted=0 and $filter 
									group by s.user, ss.smiley) a
							group by a.smiley) b
					left join
						(select s.user, ss.smiley, sum(count) count
							from shouts s join shout_smilies ss on (s.id=ss.shout_id and s.epoch=ss.shout_epoch)
							where s.deleted=0 and $filter
							group by s.user, ss.smiley) c
					on (b.smiley=c.smiley and b.max=c.count))
				on (d.smiley=b.smiley)
				left join users u on (c.user=u.id)
				left join smilies sm on (d.smiley=sm.id)
				order by d.count desc",
		'params' => array_merge($params, $params, $params),
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
/*
$queries[] = array(
		'title' => 'Word usage',
		'query' => "select w.word, a.count,
			(select concat(u.id, '$$', u.name, '$$', sum(sw2.count))
				from users u join shouts s2 on (u.id = s2.user) join shout_words sw2 on (s2.id = sw2.shout_id and s2.epoch = sw2.shout_epoch)
				where sw2.word = a.word and s2.deleted = 0 and $filter2
				group by s2.user
				order by sum(sw2.count) desc
				limit 0, 1) top
			from (select sw.word, sum(sw.count) count from shout_words sw shouts sh on (sw.shout_epoch = sh.epoch and sw.shout_id = sh.id) where sh.deleted = 0 and $filter3 group by sw.word order by sum(sw.count) desc limit 0, 20) a join words w on (a.word=w.id)",
		'processing_function' => function(&$row) {
				$link_parts = build_link_from_request('day', 'month', 'year', 'user', 'hour', 'period');
				$row[0]['word'] = '<a href="details.php?word=' . urlencode($row[0]['word']) . $link_parts . '">' . $row[0]['word'] . '</a>';

				$top = explode('$$', $row[0]['top']);
				$user_id = $top[0];
				$username = $top[1];
				$frequency = $top[2];
				$link = 'details.php?user=' . urlencode($username);
				$row[0]['top'] = "<a href=\"$link\">$username</a> (${frequency}x)";
			},
		'params' => array_merge($params, $params),
		'columns' => array('Word', 'Occurrences', 'Top user'),
		'column_styles' => array('right', 'right', 'left'),
	);
 */
/*
$queries[] = array(
		'title' => '',
		'query' => "",
		'columns' => array(),
		'column_styles' => array(),
	);
 */
$query_total = array(
		'query' => "SELECT COUNT(*) shouts FROM shouts s WHERE deleted = 0 AND $filter",
		'params' => $params,
	);

$page_title = "Spam overview: $what";
$backlink = array(
		'url' => 'overview.php',
		'text' => 'Spam overview',
	);

require_once(dirname(__FILE__) . '/../lib/stats.php');

log_data();

