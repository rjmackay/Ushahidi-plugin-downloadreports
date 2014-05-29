
/**
 * Download reports js file.
 *
 * Handles javascript stuff related to download reports function.
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author     Ushahidi Team <team@ushahidi.com> 
 * @package    Ushahidi - http://source.ushahididev.com
 * @module     Download Reports
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
 */
// Check All / Check None
function CheckAll( id, checkboxName)
{
	$("input:checkbox[name='"+checkboxName+"[]']").attr('checked', $('#' + id).is(':checked'));
}

$(document).ready(function() {

//add Not Selected values to the custom form fields that are drop downs
		$("select[id^='custom_field_']").prepend('<option value="---NOT_SELECTED---"><?php echo Kohana::lang("ui_main.not_selected"); ?></option>');
		$("select[id^='custom_field_']").val("---NOT_SELECTED---");
		$("input[id^='custom_field_']:checkbox").removeAttr("checked");
		$("input[id^='custom_field_']:radio").removeAttr("checked");
		$("input[id^='custom_field_']:text").val("");
		// Hide textareas - should really replace with a keyword search field
		$("textarea[id^='custom_field_']").parent().remove();

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
	
	/* Dynamic categories */
	
	// Category treeview
	$("#category-column-1, #category-column-2, .category-column").treeview({
	  persist: "location",
	  collapsed: true,
	  unique: false
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
