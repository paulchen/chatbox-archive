<?php 
// TODO escaping in this file?
echo '<?xml version="1.0"?>';
?>
	<messages total="<?php echo $message_data['total_shouts'] ?>" filtered="<?php echo $message_data['filtered_shouts'] ?>">
	<?php foreach($message_data['messages'] as $message): ?>
		<message id="<?php echo $message['id'] ?>" epoch="<?php echo $message['epoch'] ?>" timestamp="<?php echo date(DateTime::W3C, $message['unixdate']) ?>">
			<user id="<?php echo $message['user_id'] ?>" name="<?php echo $message['user_name'] ?>" type="<?php echo $message['color'] ?>" />
			<content><![CDATA[<?php echo $message['message'] ?>]]></content>
		</message>
	<?php endforeach; ?>
</messages>

