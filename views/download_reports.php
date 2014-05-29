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

<?php print form::open(NULL, array('id' => 'reportForm'));
	
	$result = Database::instance()->query("SHOW TABLES LIKE 'actionable'"); //see if Actionable plugin is being used.
	$actionableExists = count($result) > 0;
?>



<div class="big-block">
	<h1><?php echo Kohana::lang('ui_admin.download_reports');?></h1>
	<?php if ($form_error): ?>
	<!-- red-box -->
	<div class="red-box">
		<h3><?php echo Kohana::lang('ui_main.error');?></h3>
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
	<div class="categories report_category">
	<h2><?php echo Kohana::lang('ui_main.categories') ?></h2>
		
		<div style="clear: left; width: 100%; float: left;">
			<input type="checkbox" id="category_all" name="category_all" onclick="CheckAll(this.id, 'category')"/><strong><?php echo utf8::strtoupper(Kohana::lang('ui_main.select_all'));?></strong>
		</div>
		<?php
		$selected_categories = (!empty($form['incident_category']) AND is_array($form['incident_category']))
					? $selected_categories = $form['incident_category']
				: array();
		if (method_exists('category','form_tree'))
		{
			echo category::form_tree('category', $selected_categories, 2, TRUE);
		}
		elseif (Kohana::config('settings.ushahidi_version') >= 2.4 AND Kohana::config('settings.ushahidi_version') <= 2.5)
		{
			echo category::tree(ORM::factory('category')->find_all(), TRUE, $selected_categories, 'category', 2, TRUE);
		}
		elseif (Kohana::config('settings.ushahidi_version') < 2.4)
		{
			echo category::tree(ORM::factory('category')->find_all(), $selected_categories, 'category', 2, TRUE);
		}
		?>
	</div>
	<div>
		<h2 style="clear: both;"><?php echo Kohana::lang('ui_main.verification') ?></h2>
		<table style="width: 350px;">
			<tr>
				<td width = 20><?php print form::checkbox('verified[]', 1, TRUE); ?></td><td><?php echo Kohana::lang('ui_main.verified');?></td>
				<td width = 20><?php print form::checkbox('verified[]', 0, TRUE); ?></td><td><?php echo Kohana::lang('ui_main.unverified');?></td>
			</tr>
		</table>
		<h2>Date range</h2>
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
	
	<div id="custom_forms">

<?php
	echo "<br /><h2>Custom Fields</h2>";

	// If the user has insufficient permissions to edit report fields, we flag this for a warning message
	$show_permission_message = FALSE;

	foreach ($disp_custom_fields as $field_id => $field_property)
	{
		// Is the field required
		$isrequired = ($field_property['field_required'])
			? "<span class='required'> *</span>"
			: "";

		// Private field
		$isprivate = ($field_property['field_ispublic_visible'])
			? '<span class="private">(' . Kohana::lang('ui_main.private') . ')</span>'
			: '';

		// Workaround for situations where admin can view, but doesn't have sufficient perms to edit.
		if (isset($custom_field_mismatch))
		{
			if(isset($custom_field_mismatch[$field_id]))
			{
				if($show_permission_message == FALSE)
				{
					echo '<small>'.Kohana::lang('ui_admin.custom_forms_insufficient_permissions').'</small><br/>';
					$show_permission_message = TRUE;
				}

				echo '<strong>'.$field_property['field_name'].'</strong><br/>';
				if (isset($form['custom_field'][$field_id]))
				{
					echo $form['custom_field'][$field_id];
				}
				else
				{
					echo Kohana::lang('ui_main.no_data');;
				}
				echo '<br/><br/>';
				//echo "</div>";
				continue;
			}
		}

		// Give all the elements an id so they can be accessed easily via javascript
		$id_name = 'id="custom_field_'.$field_id.'"';

		// Get the field value
		$field_value = ( ! empty($form['custom_field'][$field_id]))
			? $form['custom_field'][$field_id]
			: $field_property['field_default'];

		if ($field_property['field_type'] == 1)
		{
			// Text Field
			echo "<div class=\"report_row\" id=\"custom_field_row_" . $field_id ."\">";

			$field_options = customforms::get_custom_field_options($field_id);

			if (isset($field_options['field_hidden']) AND !isset($editor))
			{
				if($field_options['field_hidden'] == 1)
				{
					echo form::hidden($field_property['field_name'], $field_value);
				}
				else
				{
					echo "<h4>" . $field_property['field_name'] . $isrequired . " " . $isprivate . "</h4>";
					echo form::input('custom_field['.$field_id.']', $field_value, $id_name .' class="text custom_text"');
				}
			}
			else
			{
				echo "<h4>" . $field_property['field_name'] . $isrequired . " " . $isprivate . "</h4>";
				echo form::input('custom_field['.$field_id.']', $field_value, $id_name .' class="text custom_text"');
			}
			echo "</div>";
		}
		elseif ($field_property['field_type'] == 2)
		{
			// TextArea Field
			$field_options = customforms::get_custom_field_options($field_id);
			if (isset($field_options['field_datatype']))
			{
				$extra_fields = $id_name . ' class="textarea custom_text" rows="3"';

				if ($field_options['field_datatype'] == 'text')
				{
					echo "<div class=\"report_row\" id=\"custom_field_row_" . $field_id ."\">";
					echo "<h4>" . $field_property['field_name'] . $isrequired . " " . $isprivate . "</h4>";
					echo form::textarea('custom_field['.$field_id.']', $field_value, $extra_fields);
					echo "</div>";
				}

				if ($field_options['field_datatype'] == 'markup')
				{
					echo "<div class=\"report_row\" id=\"custom_field_row_" . $field_id ."\">";
					echo "<h4>" . $field_property['field_name'] . $isrequired . " " . $isprivate . "</h4>";
					echo form::textarea('custom_field['.$field_id.']', $field_value, $extra_fields, false);
					echo "</div>";
				}

				if ($field_options['field_datatype'] == 'javascript')
				{
					if(isset($editor))
					{
						echo "<div class=\"report_row\" id=\"custom_field_row_" . $field_id ."\">";
						echo "<h4>" . $field_property['field_name'] . $isrequired . " " . $isprivate . "</h4>";
						echo form::textarea('custom_field['.$field_id.']', $field_value, $extra_fields, false);
						echo "</div>";
					}
					else
					{
						echo '<script type="text/javascript">' . $field_property['field_default'] . '</script>';
					}
				}
			}
			else
			{
				echo "<div class=\"report_row\" id=\"custom_field_row_" . $field_id ."\">";
				echo "<h4>" . $field_property['field_name'] . $isrequired . " " . $isprivate . "</h4>";
				echo form::textarea('custom_field['.$field_id.']', $field_value, $id_name .' class="textarea custom_text" rows="3"');
				echo "</div>";
			}
		}
		elseif ($field_property['field_type'] == 3)
		{ // Date Field
			echo "<div class=\"report_row\" id=\"custom_field_row_" . $field_id ."\">";
			echo "<h4>" . $field_property['field_name'] . $isrequired . " " . $isprivate . "</h4>";
			echo form::input('custom_field['.$field_id.']', $field_value, ' id="custom_field_'.$field_id.'" class="text"');
			echo "<script type=\"text/javascript\">
				$(document).ready(function() {
				$(\"#custom_field_".$field_id."\").datepicker({
				showOn: \"both\",
				buttonImage: \"".url::file_loc('img')."media/img/icon-calendar.gif\",
				buttonImageOnly: true
				});
				});
			</script>";
			echo "</div>";
		}
		elseif ($field_property['field_type'] >=5 AND $field_property['field_type'] <=7)
		{
			// Multiple-selector Fields
			echo "<div class=\"report_row\" id=\"custom_field_row_" . $field_id ."\">";
			echo "<h4>" . $field_property['field_name'] . $isrequired . " " . $isprivate . "</h4>";
			$defaults = explode('::',$field_property['field_default']);

			$default = (isset($defaults[1])) ? $defaults[1] : 0;

			if (isset($form['custom_field'][$field_id]))
			{
				if($form['custom_field'][$field_id] != '')
				{
					$default = $form['custom_field'][$field_id];
				}
			}

			$options = explode(',',$defaults[0]);
			$html ='';
			switch ($field_property['field_type'])
			{
				case 5:
					foreach($options as $option)
					{
						$option = trim($option);
						$set_default = ($option == trim($default));

						$html .= "<span class=\"custom-field-option\">";
						$html .= form::label('custom_field['.$field_id.']'," ".$option." ");
						$html .= form::radio('custom_field['.$field_id.']',$option, $set_default, $id_name);
						$html .= "</span>";
					}
					break;
				case 6:
					$multi_defaults = !empty($field_property['field_response'])? explode(',', $field_property['field_response']) : NULL;

					$cnt = 0;
					$html .= "<table border=\"0\">";
					foreach($options as $option)
					{
						if ($cnt % 2 == 0)
						{
							$html .= "<tr>";
						}

						$html .= "<td>";
						$set_default = FALSE;

						if (!empty($multi_defaults))
						{
							foreach($multi_defaults as $key => $def)
							{
								$set_default = (trim($option) == trim($def));
								if ($set_default)
									break;
							}
						}
						$option = trim($option);
						$html .= "<span class=\"custom-field-option\">";
						$html .= form::checkbox("custom_field[".$field_id.'-'.$cnt.']', $option, $set_default, $id_name);
						$html .= form::label("custom_field[".$field_id.']'," ".$option);
						$html .= "</span>";

						$html .= "</td>";
						if ($cnt % 2 == 1 OR $cnt == count($options)-1)
						{
							$html .= "</tr>";
						}

						$cnt++;
					}
					// XXX Hack to deal with required checkboxes that are submitted with nothing checked
					$html .= "</table>";
					$html .= form::hidden("custom_field[".$field_id."-BLANKHACK]",'',$id_name);
					break;
				case 7:
					$ddoptions = array();
					// Semi-hack to deal with dropdown boxes receiving a range like 0-100
					if (preg_match("/[0-9]+-[0-9]+/",$defaults[0]) AND count($options == 1))
					{
						$dashsplit = explode('-',$defaults[0]);
						$start = $dashsplit[0];
						$end = $dashsplit[1];
						for($i = $start; $i <= $end; $i++)
						{
							$ddoptions[$i] = $i;
						}
					}
					else
					{
						foreach($options as $op)
						{
							$op = trim($op);
							$ddoptions[$op] = $op;
						}
					}

					$html .= form::dropdown("custom_field[".$field_id.']',$ddoptions,$default,$id_name);
					break;

			}

			echo $html;
			echo "</div>";
		}
		elseif ($field_property['field_type'] == 8 )
		{
			//custom div
			if ($field_property['field_default'] != "")
			{
				echo "<div class=\"" . $field_property['field_default'] . "\" $id_name>";
			}
			else
			{
				echo "<div class=\"custom_div\" $id_name >";
			}

			$field_options = customforms::get_custom_field_options($field_id);

			if (isset($field_options['field_toggle']) && !isset($editor))
			{
				if ($field_options['field_toggle'] >= 1)
				{
					echo "<script type=\"text/javascript\">
						$(function(){
						$('#custom_field_" .$field_id . "_link').click(function() {
  							$('#custom_field_" .$field_id . "_inner').toggle('slow', function() {
    						// Animation complete.
  							});
						});
					});
					</script>";
					echo "<a href=\"javascript:void(0);\" id=\"custom_field_" . $field_id ."_link\">";
					echo "<h2>" . $field_property['field_name'] . "</h2>";
					echo "</a>";

					$inner_visibility = ($field_options['field_toggle'] == 2) ? "none": "visible";

					echo "<div id=\"custom_field_" . $field_id . "_inner\" style=\"display:$inner_visibility;\">";
				}
				else
				{
					echo "<h2>" . $field_property['field_name'] . "</h2>";
					echo "<div id=\"custom_field_" . $field_id . "_inner\">";
				}
			}
			else
			{
				echo "<h2>" . $field_property['field_name'] . "</h2>";
				echo "<div id=\"custom_field_" . $field_id . "_inner\">";
			}
		}
		elseif ($field_property['field_type'] == 9)
		{
			// End of custom div
			echo "</div></div>";
			if (isset($editor))
			{
				echo "<h4 style=\"padding-top:0px;\">-------" . Kohana::lang('ui_admin.divider_end_field') . "--------</h4>";
			}
		}


		if (isset($editor))
		{
			$form_fields = '';
			$visibility_selection = array('0' => Kohana::lang('ui_admin.anyone_role'));
			$roles = ORM::factory('role')->find_all();
			foreach ($roles as $role)
			{
				$visibility_selection[$role->id] = ucfirst($role->name);
			}

			// Check if the field is required
			$isrequired = ($field_property['field_required'])
				? Kohana::lang('ui_admin.yes')
				: Kohana::lang('ui_admin.no');

			$form_fields .= "	<div class=\"forms_fields_edit\" style=\"clear:both\">
			<a href=\"javascript:fieldAction('e','EDIT',".$field_id.",".$form['id'].",".$field_property['field_type'].");\">EDIT</a>&nbsp;|&nbsp;
			<a href=\"javascript:fieldAction('d','DELETE',".$field_id.",".$form['id'].",".$field_property['field_type'].");\">DELETE</a>&nbsp;|&nbsp;
			<a href=\"javascript:fieldAction('mu','MOVE',".$field_id.",".$form['id'].",".$field_property['field_type'].");\">MOVE UP</a>&nbsp;|&nbsp;
			<a href=\"javascript:fieldAction('md','MOVE',".$field_id.",".$form['id'].",".$field_property['field_type'].");\">MOVE DOWN</a>
			</div>";
			echo $form_fields;
		}

		if ($field_property['field_type'] != 8 AND $field_property['field_type'] != 9)
		{
			//if we're doing custom divs we don't want these div's to get in the way.
			//echo "</div>";
		}
	}
?>
</div>
<!-- this is above the download buttons and below the custom form fields dropdowns. --> 
<?php
if($actionableExists){
	echo"<div id=\"actionable-filters\">";
		echo "<h2>Actionable Status</h2>";
		print form::label('actionable', Kohana::lang('actionable.all').': ');
		print form::radio('actionable', 'all', TRUE).'<br />';
		print form::label('actionable', Kohana::lang('actionable.actionable').': ');
		print form::radio('actionable', 'actionable').'<br />';
		print form::label('actionable', Kohana::lang('actionable.urgent').': ');
		print form::radio('actionable', 'urgent').'<br />';
		print form::label('actionable', Kohana::lang('actionable.action_taken').': ');
		print form::radio('actionable', 'action_taken').'<br />';
		print form::label('actionable', Kohana::lang('actionable.not_actionable').': ');
		print form::radio('actionable', 'not_actionable').'<br />';
	echo"</div>";
}
?>
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
