<?php
function add_user_link(&$row) {
	$row[0]['name'] = '<a href="details.php?user=' . urlencode($row[0]['name']) . '">' . $row[0]['name'] . '</a>';
}

function spammer_smiley(&$row) {
	if($row[0]['top_spammer'] != '') {
		$parts = explode('$$', $row[0]['top_spammer']);
		$row[0]['top_spammer'] = "<a href=\"details.php?user={$parts[1]}\">{$parts[1]}</a> ({$parts[2]}x)";
	}
	else {
		$row[0]['top_spammer'] = '-';
	}

	if($row[0]['popular_smiley'] != '') {
		$parts = explode('$$', $row[0]['popular_smiley']);
		$row[0]['popular_smiley'] = "<a href=\"details.php?smiley={$parts[0]}\"><img src=\"images/smilies/{$parts[1]}\" alt=\"\" /></a> ({$parts[2]}x)";
	}
	else {
		$row[0]['popular_smiley'] = '-';
	}

	if($row[0]['popular_word'] != '') {
		$parts = explode('$$', $row[0]['popular_word']);
		$row[0]['popular_word'] = "<a href=\"details.php?word=" . urlencode($parts[1]) . "\">{$parts[1]}</a> ({$parts[2]}x)";
	}
	else {
		$row[0]['popular_word'] = '-';
	}
}

function messages_per_hour(&$row) {
	$row[0]['hour'] = '<a href="details.php?hour=' . $row[0]['hour'] . '">' . $row[0]['hour'] . '</a>';
	spammer_smiley($row);
}

function messages_per_month(&$row) {
	$parts = explode('-', $row[0]['monthx']);
	$year = $parts[0];
	$month = $parts[1];
	$row[0]['monthx'] = "<a href=\"details.php?month={$parts[1]}&amp;year={$parts[0]}\">{$row[0]['monthx']}</a>";
	spammer_smiley($row);
}

function messages_per_year(&$row) {
	$row[0]['year'] = "<a href=\"details.php?year=" . $row[0]['year'] . "\">" . $row[0]['year'] . '</a>';
	spammer_smiley($row);
}

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
						where deleted=0
						group by u.name, u.id) d
					left join
					(
						(select a.user, max(a.count) max
							from (select s.user, sum(sm.count) count from shouts s join shout_smilies sm on (s.id=sm.shout_id and s.epoch=sm.shout_epoch) where deleted=0 group by s.user, sm.smiley) a
							group by a.user) b
						left join
						(select s.user, sm.smiley, sum(sm.count) count from shouts s join shout_smilies sm on (s.id=sm.shout_id and s.epoch=sm.shout_epoch) where deleted=0 group by s.user, sm.smiley) c
						on (b.user = c.user and b.max = c.count)) on (d.id = b.user)
					left join smilies sm on (c.smiley = sm.id)
					left join
					(
						(select e.user, max(e.count) max
							from (select s.user, sum(sw.count) count from shouts s join shout_words sw on (s.id=sw.shout_id and s.epoch=sw.shout_epoch) where deleted=0 group by s.user, sw.word) e
							group by e.user) f
						left join
						(select s.user, sw.word, sum(sw.count) count from shouts s join shout_words sw on (s.id=sw.shout_id and s.epoch=sw.shout_epoch) where deleted=0 group by s.user, sw.word) g
						on (f.user = g.user and f.max = g.count)) on (d.id = f.user)
					left join words w on (g.word = w.id)
				order by d.shouts desc, average_shouts_per_day asc, d.name asc",
		'processing_function' => array('add_user_link', 'smiley_column', 'word_column'),
		'processing_function_all' => array('duplicates0', 'insert_position', 'ex_aequo2'),
		'columns' => array('Position', 'Username', 'Messages', 'Avg msgs/day', 'Total smilies', 'Avg smilies/msg', 'Most popular smiley', 'Most popular word'),
		'column_styles' => array('right', 'left', 'right', 'right', 'right', 'right', 'left', 'left'),
		'derived_queries' => array(
			array(
				'title' => 'Top spammers, ordered by messages per day',
				'transformation_function' => 'top_spammers',
				'processing_function' => array('add_user_link', 'smiley_column', 'word_column'),
				'processing_function_all' => 'ex_aequo3',
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
					(select hour, count(s.id) count from shouts s where deleted=0 group by hour) j on (h.hour=j.hour)
					left join
					(
						(select hour, max(count) max from (select \"user\", hour, count(*) count from shouts where deleted=0 group by \"user\", hour) a group by hour) b
						left join
						(select \"user\", hour, count(*) count from shouts where deleted=0 group by \"user\", hour) c
						on (b.hour=c.hour and b.max=c.count)
					) on (j.hour=b.hour)
					left join users u on (c.user=u.id)
					left join
					(
						(select e.hour, max(e.count) max
							from (select s.hour, sum(sm.count) count from shouts s join shout_smilies sm on (s.id=sm.shout_id and s.epoch=sm.shout_epoch) where deleted=0 group by s.hour, sm.smiley) e
							group by e.hour) d
						left join
						(select s.hour, sm.smiley, sum(sm.count) count from shouts s join shout_smilies sm on (s.id=sm.shout_id and s.epoch=sm.shout_epoch) where deleted=0 group by s.hour, sm.smiley) f
						on (d.hour = f.hour and d.max = f.count)
					) on (j.hour=d.hour)
					left join smilies sm on (f.smiley = sm.id)
					left join
					(
						(select h.hour, max(h.count) max
							from (select s.hour, sum(sw.count) count from shouts s join shout_words sw on (s.id=sw.shout_id and s.epoch=sw.shout_epoch) where deleted=0 group by s.hour, sw.word) h
							group by h.hour) g
						left join
						(select s.hour, sw.word, sum(sw.count) count from shouts s join shout_words sw on (s.id=sw.shout_id and s.epoch=sw.shout_epoch) where deleted=0 group by s.hour, sw.word) i
						on (g.hour = i.hour and g.max = i.count)
					) on (j.hour=g.hour)
					left join words w on (i.word = w.id)
					order by h.hour asc",
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
 */
/*
$queries[] = array(
		'title' => 'Busiest days',
		'query' => "select a.day, a.shouts,
					(select concat(s.user, '$$', u.name, '$$', count(s.id)) from shouts s join users u on (s.user = u.id) where cast(s.day as text)=a.qday and cast(s.month as text)=a.qmonth and cast(s.year as text)=a.qyear and deleted = 0 group by s.user, u.name order by count(s.id) desc limit 1) top_spammer,
					(select concat(ss.smiley, '$$', sm.filename, '$$', sum(ss.count)) from shouts s join shout_smilies ss on (s.id = ss.shout_id and s.epoch = ss.shout_epoch) join smilies sm on (ss.smiley = sm.id) where cast(s.day as text)=a.qday and cast(s.month as text)=a.qmonth and cast(s.year as text)=a.qyear and deleted=0 group by ss.smiley, sm.filename order by sum(ss.count) desc limit 1) popular_smiley
			from
			(select to_char(date+interval '1 hour', 'YYYY-MM-DD') \"day\", to_char(date+interval '1 hour', 'YYYY') qyear, to_char(date+interval '1 hour', 'MM') qmonth, to_char(date+interval '1 hour', 'DD') qday, count(*) as shouts
					from shouts where deleted = 0
					group by date, qyear, qmonth, qday
					order by count(*) desc
					limit 10) a",
		'processing_function' => function(&$row) {
				$parts = explode('-', $row[0]['day']);
				$year = $parts[0];
				$month = $parts[1];
				$day = $parts[2];
				$row[0]['day'] = "<a href=\"details.php?day=$day&amp;month=$month&amp;year=$year\">" . $row[0]['day'] . '</a>';
				spammer_smiley($row);
			},
		'columns' => array('Day', 'Messages', 'Top spammer', 'Most popular smiley'),
		'column_styles' => array('left', 'right', 'left', 'left'),
	);
$queries[] = array(
		'title' => 'Messages per month',
		'query' => "select to_char(date+interval '1 hour', 'YYYY-MM') monthx, count(*) as shouts,
					(select concat(s.user, '$$', u.name, '$$', count(s.id)) from shouts s join users u on (s.user = u.id) where cast(s.month as text)=to_char(sx.date, 'MM') and cast(s.year as text)=to_char(sx.date, 'YYYY') and deleted=0 group by s.user, u.name order by count(s.id) desc limit 1) top_spammer,
					(select concat(ss.smiley, '$$', sm.filename, '$$', sum(ss.count)) from shouts s join shout_smilies ss on (s.id = ss.shout_id and s.epoch = ss.shout_epoch) join smilies sm on (ss.smiley = sm.id) where cast(s.month as text)=to_char(sx.date, 'MM') and cast(s.year as text)=to_char(sx.date, 'YYYY') and deleted=0 group by ss.smiley, sm.filename order by sum(ss.count) desc limit 1) popular_smiley
				from shouts sx
				where deleted = 0
				group by sx.date
				order by monthx asc",
		'processing_function' => 'messages_per_month',
		'columns' => array('Month', 'Messages', 'Top spammer', 'Most popular smiley'),
		'column_styles' => array('left', 'right', 'left', 'left'),
		'derived_queries' => array(
			array(
				'title' => 'Messages per month, ordered by number of messages',
				'transformation_function' => 'busiest_time',
				'processing_function' => 'messages_per_month',
				'processing_function_all' => 'ex_aequo2',
				'columns' => array('Position', 'Month', 'Messages', 'Top spammer', 'Most popular smiley'),
				'column_styles' => array('right', 'left', 'right', 'left', 'left'),
			),
		),
	);
 */
$queries[] = array(
		'title' => 'Messages per year',
		'query' => "select j.year, j.count shouts, concat(c.user, '$$', u.name, '$$', c.count) top_spammer,
                                        concat(f.smiley, '$$', sm.filename, '$$', f.count) popular_smiley, concat(i.word, '$$', w.word, '$$', i.count) popular_word
                                from (select year, count(s.id) count from shouts s where deleted=0 group by year) j
                                        left join
                                        (
                                                (select year, max(count) max from (select \"user\", year, count(*) count from shouts where deleted=0 group by \"user\", year) a group by year) b
                                                left join
                                                (select \"user\", year, count(*) count from shouts where deleted=0 group by \"user\", year) c
                                                on (b.year=c.year and b.max=c.count)
                                        ) on (j.year=b.year)
                                        left join users u on (c.user=u.id)
                                        left join
                                        (
                                                (select e.year, max(e.count) max
                                                        from (select s.year, sum(sm.count) count from shouts s join shout_smilies sm on (s.id=sm.shout_id and s.epoch=sm.shout_epoch) where deleted=0 group by s.year, sm.smiley) e
                                                        group by e.year) d
                                                left join
                                                (select s.year, sm.smiley, sum(sm.count) count from shouts s join shout_smilies sm on (s.id=sm.shout_id and s.epoch=sm.shout_epoch) where deleted=0 group by s.year, sm.smiley) f
                                                on (d.year = f.year and d.max = f.count)
                                        ) on (j.year=d.year)
                                        left join smilies sm on (f.smiley = sm.id)
                                        left join
                                        (
                                                (select h.year, max(h.count) max
                                                        from (select s.year, sum(sw.count) count from shouts s join shout_words sw on (s.id=sw.shout_id and s.epoch=sw.shout_epoch) where deleted=0 group by s.year, sw.word) h
                                                        group by h.year) g
                                                left join
                                                (select s.year, sw.word, sum(sw.count) count from shouts s join shout_words sw on (s.id=sw.shout_id and s.epoch=sw.shout_epoch) where deleted=0 group by s.year, sw.word) i
                                                on (g.year = i.year and g.max = i.count)
                                        ) on (j.year=g.year)
                                        left join words w on (i.word = w.id)
                                        order by j.year asc",
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
/*
$queries[] = array(
		'title' => 'Smiley usage',
		'query' => "select sm.filename, d.count, concat(u.id, '$$', u.name, '$$', c.count) top
				from
					(select ss.smiley, coalesce(sum(ss.count), 0) count
						from shouts s join shout_smilies ss on (s.id=ss.shout_id and s.epoch=ss.shout_epoch)
						where deleted=0
						group by ss.smiley) d
				left join
					(
						(select a.smiley, max(count) max
							from
								(select s.user, ss.smiley, sum(count) count
									from shouts s join shout_smilies ss on (s.id=ss.shout_id and s.epoch=ss.shout_epoch)
									where s.deleted=0 
									group by s.user, ss.smiley) a
							group by a.smiley) b
					left join
						(select s.user, ss.smiley, sum(count) count
							from shouts s join shout_smilies ss on (s.id=ss.shout_id and s.epoch=ss.shout_epoch)
							where s.deleted=0
							group by s.user, ss.smiley) c
					on (b.smiley=c.smiley and b.max=c.count))
				on (d.smiley=b.smiley)
				left join users u on (c.user=u.id)
				left join smilies sm on (d.smiley=sm.id)
				order by d.count desc",
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
		'title' => 'Word usage (top 20)',
		'query' => "select w.word, d.count, concat(u.id, '$$', u.name, '$$', c.count) top
				from
					(select sw.word, coalesce(sum(sw.count), 0) count
						from shouts s join shout_words sw on (s.id=sw.shout_id and s.epoch=sw.shout_epoch)
						where deleted=0
						group by sw.word
						limit 20) d
				left join
					(
						(select a.word, max(count) max
							from
								(select s.user, sw.word, sum(count) count
									from shouts s join shout_words sw on (s.id=sw.shout_id and s.epoch=sw.shout_epoch)
									where s.deleted=0 
									group by s.user, sw.word) a
							group by a.word) b
					left join
						(select s.user, sw.word, sum(count) count
							from shouts s join shout_words sw on (s.id=sw.shout_id and s.epoch=sw.shout_epoch)
							where s.deleted=0
							group by s.user, sw.word) c
					on (b.word=c.word and b.max=c.count))
				on (d.word=b.word)
				left join users u on (c.user=u.id)
				join words w on (d.word=w.id)
				order by d.count desc",
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
 */
/*
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
		'query' => 'SELECT COUNT(*) shouts FROM shouts WHERE deleted = 0',
		'params' => array(),
	);

$page_title = 'Spam overview';
$backlink = array(
		'url' => 'index.php',
		'text' => 'Chatbox archive',
	);

require_once(dirname(__FILE__) . '/../lib/common.php');

/* cached data from misc/update_stats.php */
$extra_stats = $memcached->get("${memcached_prefix}_stats_min_max");

require_once(dirname(__FILE__) . '/../lib/stats.php');

log_data();

