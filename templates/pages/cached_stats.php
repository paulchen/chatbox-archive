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
