<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Download Reports view page.
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
 */
?>

<?php print form::open(NULL, array('id' => 'reportForm'));?>

<div class="big-block">
	<h1><?php echo Kohana::lang('ui_admin.download_reports');?></h1>
	<?php if ($form_error): ?>
	<!-- red-box -->
	<div class="red-box">
		<h3>Error!</h3>
		<ul>
			<?php
				foreach ($errors as $error_item => $error_description)
				{
					// print "<li>" . $error_description . "</li>";
					print (!$error_description) ? '' : "<li>" . $error_description . "</li>";
				}
			?>
		</ul>
	</div>
	<?php endif; ?>
	<div class="categories">
		<table>
			<tr>
				<td width="20">
				<input type="checkbox" id="category_all" name="category_all" onclick="CheckAll(this.id)"/>
				</td>
				<td><strong><?php echo strtoupper(Kohana::lang('ui_main.select_all'));?></strong></td>
			</tr>
			<?php
				foreach ($categories as $category)
				{
				// excludes trusted reports	category
				if ($category->category_title == "Trusted Reports")
				continue;
			?>
			<tr>
				<td><?php print form::checkbox('category[]', $category->id, FALSE);?></td><td colspan="2"><?php echo $category->category_title;?></td>
			</tr>
			<?php
				}
			?>
		</table>
		<table>
			<tr>
				<td><h2><?php echo Kohana::lang('ui_admin.from_date');?>:</h2></td>
				<td><?php print form::input('from_date', $form['from_date'], ' class="text", SIZE=7');?></td>
			</tr>
			<tr>
				<td><h2><?php echo Kohana::lang('ui_admin.to_date');?>:</h2></td>
				<td><?php print form::input('to_date', $form['to_date'], ' class="text", SIZE=7');?></td>
			</tr>
		</table>
	</div>
	<div class="box">
		<table>
			<tr>
				<td width="5"><?php print form::radio('formato', 0, 1);?></td><td width="50">CSV</td>
				<td width="5"><?php print form::radio('formato', 1, 0);?></td><td width="50">KML</td>
				<td width="50">
				<input id="save_only" type="image" src="<?php print url::file_loc('img');?>media/img/admin/btn-download.gif" class="save-rep-btn" />
				</td>
			</tr>
		</table>
	</div>
	<div id="form_error"></div>
	<?php print form::close();?>
</div>
<?php

	// Load jQuery
	echo html::script(url::file_loc('js') . 'media/js/jquery.validate.min', true);
	echo html::script(url::file_loc('js') . 'media/js/jquery.ui.min', true);
?>

<script type="text/javascript" >

// Check All / Check None
function CheckAll( id )
{
	$("td > input:checkbox").attr('checked', $('#' + id).is(':checked'));
}

$(document).ready(function() {

	$("#from_date").datepicker({
		showOn: "both",
		buttonImage: "<?php echo $calendar_img;?>",
		buttonImageOnly: true,
		changeMonth: true,
		changeYear: true
	});

	$("#to_date").datepicker({
		showOn: "both",
		buttonImage: "<?php echo $calendar_img;?>",
		buttonImageOnly: true,
		changeMonth: true,
		changeYear: true
	});

	$("#reportForm").validate({

		rules:
		{
			"category[]": {
			required: true
			},
			from_date: {
				required: true,
				date: true
			},
			to_date: {
				required: true,
				date: true
			}
		},
		messages:
		{
			"category[]":{
				required: "<?php echo Kohana::lang('download_reports.category.required');?>"
				},
				from_date: {
					required: "<?php echo Kohana::lang('download_reports.from_date.required');?>",
					date: "<?php echo Kohana::lang('download_reports.from_date.date');?>"
				},
				to_date: {
					required: "<?php echo Kohana::lang('download_reports.to_date.required');?>",
					date: "<?php echo Kohana::lang('download_reports.to_date.date');?>"
				}
			},
			errorPlacement: function(error, element) {
			error.appendTo("#form_error");
		}
	});
});
</script>
