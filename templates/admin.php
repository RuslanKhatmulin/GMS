	<div class="wrap">
		<br>
		<h2 style="text-align: center"><?php _e("Wasi Admin", 'wptodo'); ?></h2>
		<br>
		<?php
		\Inc\Pages\Admin::wptodo_add_button();
		?>
		<table id="todo" class="display" style="width:100%">
			<thead>
				<tr>
					<th>Photo</th>
					<th>name</th>
					<th>state</th>
				</tr>
			</thead>
			<tbody>
				<?php
				self::wptodo_tasks();
				?>
			</tbody>
		</table>
	</div>
	<?php
	\Inc\Pages\Admin::wptodo_add_form();
	?>
	<script type="text/javascript">
		jQuery(document).ready(function() {
			jQuery('#todo').DataTable({
				responsive: true
			});
		});
	</script>