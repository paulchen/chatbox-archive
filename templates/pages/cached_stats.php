<div>
<h2>Periods with highest/lowest number of shouts</h2>
<table>
<tr><th>Period</th><th></th><th>Start</th><th>End</th><th>Number of shouts</th><th>Top spammer</th><th>Top smiley</th></tr>
<?php foreach($periods as $period): ?>
<tr>
	<td><?php echo $period['name'] ?></td>
	<td style="padding-left: 10px;">Fewest shouts</td>
	<td style="padding-left: 10px;"><?php echo date('Y-m-d H:i', $period['min']['start_data']['date']) ?></td>
	<td style="padding-left: 10px;"><?php echo date('Y-m-d H:i', $period['min']['end_data']['date']) ?></td>
	<td style="text-align: right; padding-left: 10px;"><?php echo $period['min']['count'] ?></td>
	<td style="padding-left: 10px;"><a href="details.php?user=<?php echo $period['min']['spammer']['name'] ?>"><?php echo $period['min']['spammer']['name'] ?></a> (<?php echo $period['min']['spammer']['shouts'] ?>)</td>
	<td style="padding-left: 10px;"><a href="details.php?smiley=<?php echo $period['min']['smiley']['id'] ?>"><img src="images/smilies/<?php echo $period['min']['smiley']['filename'] ?>" alt="" /></a> (<?php echo $period['min']['smiley']['smilies'] ?>)</td>
</tr>
<tr>
	<td></td>
	<td style="padding-left: 10px;">Most shouts</td>
	<td style="padding-left: 10px;"><?php echo date('Y-m-d H:i', $period['max']['start_data']['date']) ?></td>
	<td style="padding-left: 10px;"><?php echo date('Y-m-d H:i', $period['max']['end_data']['date']) ?></td>
	<td style="text-align: right; padding-left: 10px;"><?php echo $period['max']['count'] ?></td>
	<td style="padding-left: 10px;"><a href="details.php?user=<?php echo $period['max']['spammer']['name'] ?>"><?php echo $period['max']['spammer']['name'] ?></a> (<?php echo $period['max']['spammer']['shouts'] ?>)</td>
	<td style="padding-left: 10px;"><a href="details.php?smiley=<?php echo $period['max']['smiley']['id'] ?>"><img src="images/smilies/<?php echo $period['max']['smiley']['filename'] ?>" alt="" /></a> (<?php echo $period['max']['smiley']['smilies'] ?>)</td>
</tr>
<?php endforeach; ?>
</table>
</div>

<hr />

<div>
<h2>Which users are talking to each other?</h2>
<div style="padding-bottom: 10px;">
This breakdown is based upon a score <em>s(A, B)</em> for two users <em>A</em> and <em>B</em> that indicates how often <em>A</em> and <em>B</em> seem to have talked to each other. <em>s(A, B)</em> is increased by <em>1</em> whenever there are a message by user <em>A</em> and one by user <em>B</em> such that
<ul><li>both messages were submitted in a period of at most five minutes or</li>
<li>there are no more than four other messages between these two messages (in chronological order).</li>
</ul>
A higher value of <em>s(A, B)</em> indicates that <em>A</em> and <em>B</em> seem to have talked to each other more frequently. <em>s(A, B)=0</em> (which is not shown in the table below) indicates that <em>A</em> and <em>B</em> seem to have never talked to each other.<br /><br />
In the table below, for each user <em>A</em> in the left column, the 20 users <em>B</em> having the highest score <em>s(A, B)</em> are listed in descending order of <em>s(A, B)</em>.
</div>
<table>
<tr><th style="text-align: left;">User</th><th style="text-align: left;">Dialog partner(s)</th></tr>
<?php foreach($conversation_points as $user => $users): ?>
	<tr><td style="vertical-align: top;"><strong><?php echo $user ?></strong></td>
	<td style="padding-bottom: 10px;">
<?php
	$index=0;
	foreach($users as $name => $score) {
		if($index < 20) {
			echo "<strong>$name</strong> ($score) ";
		}
		else if($index == 20) {
			echo "(" . (count($users)-20) . " more...)";
		}
		$index++;
	}
?>
	</td></tr>
<?php endforeach; ?>
</table>
</div>

