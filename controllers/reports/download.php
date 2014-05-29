<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Download Reports Controller.
 * This controller will take care of downloading reports.
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author Marco Gnazzo
 * @Modifield by Leif Percifield, Michael Kahane for CEGSS, OSF and Parsons
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
 */

Class Download_Controller extends Main_Controller {

	function __construct()
	{
		parent::__construct();
	}

	function index()
	{
		$this->template->this_page = 'download';
		$this->template->header->this_page = 'download';
		$this->template->content = new View('download_reports');
		$custom_forms = customforms::get_custom_form_fields();
		$this->template->content->disp_custom_fields = $custom_forms;
		$this->template->content->search_form = TRUE;
		$this->template->content->calendar_img = url::base() . "media/img/icon-calendar.gif";
		$this->template->content->title = Kohana::lang('ui_admin.download_reports');
		
		
		$result = Database::instance()->query("SHOW TABLES LIKE 'actionable'"); //see if Actionable plugin is being used.
		$actionableExists = count($result) > 0;

		// Javascript Header
		$this->themes->js = new View('download_reports_js');
		$this->themes->js->calendar_img = url::base() . "media/img/icon-calendar.gif";
		$this->themes->treeview_enabled = TRUE;

		$this->template->header->header_block = $this->themes->header_block();
		$this->template->footer->footer_block = $this->themes->footer_block();

		// Select first and last incident
		$from = orm::factory('incident')->orderby('incident_date', 'asc')->find();
		$to = orm::factory('incident')->orderby('incident_date', 'desc')->find();

		$from_date = substr($from->incident_date, 5, 2) . "/" . substr($from->incident_date, 8, 2) . "/" . substr($from->incident_date, 0, 4);

		$to_date = substr($to->incident_date, 5, 2) . "/" . substr($to->incident_date, 8, 2) . "/" . substr($to->incident_date, 0, 4);

		$form = array('category' => '', 'verified' => '', 'category_all' => '', 'from_date' => '', 'to_date' => '');

		$errors = $form;
		$form_error = FALSE;
		$form['from_date'] = $from_date;
		$form['to_date'] = $to_date;
		$table_prefix = Kohana::config('database.default.table_prefix');
		$query = array();

		if ($_POST)
		{
			$csv_headers = array("#","INCIDENT TITLE","INCIDENT DATE","LOCATION","DESCRIPTION","CATEGORY","LATITUDE","LONGITUDE","APPROVED","VERIFIED");
			$report_csv = '';
			// Instantiate Validation, use $post, so we don't overwrite $_POST fields with our own things
			$post = Validation::factory($_POST);

			//  Add some filters
			$post->pre_filter('trim', TRUE);

			// Add some rules, the input field, followed by a list of checks, carried out in order
			$post->add_rules('category.*', 'required', 'numeric');
			$post->add_rules('verified.*', 'required', 'numeric', 'between[0,1]');
			$post->add_rules('formato', 'required', 'numeric', 'between[0,1]');
			$post->add_rules('from_date', 'required', 'date_mmddyyyy');
			$post->add_rules('to_date', 'required', 'date_mmddyyyy');

			// Validate the report dates, if included in report filter
			if (!empty($_POST['from_date']) && !empty($_POST['to_date']))
			{
				// TO Date not greater than FROM Date?
				if (strtotime($_POST['from_date']) > strtotime($_POST['to_date']))
				{
					$post->add_error('to_date', 'range_greater');
				}
			}

			// $post validate check
			if ($post->validate())
			{
				// Check child categories too
				$categories = ORM::factory('category')->select('id')->in('parent_id', $post->category)->find_all();
				foreach($categories as $cat)
				{
					$post->category[] = $cat->id;
				}

				$incident_query = ORM::factory('incident')->select('DISTINCT incident.id')->select('incident.*')->where('incident_active', 1);
				$incident_query->in('category_id', $post->category);

				// If only unverified selected
				if (in_array('0', $post->verified) && !in_array('1', $post->verified))
				{
					$incident_query->where('incident_verified', 0);
				}
				// If only verified selected
				elseif (!in_array('0', $post->verified) && in_array('1', $post->verified))
				{
					$incident_query->where('incident_verified', 1);
				}
				// else - do nothing
				
				// THIS IS CALLED IF THERE ARE ANY CUSTOM FIELDS DEFINED FOR THIS INSTALLATION OF USHAHIDI	
				if(isset($post->custom_field))
				{		
					$where_text = "";
					$i = 0;
					$query_text = 'CREATE TEMPORARY TABLE sorted AS (SELECT
					  incident_id,';
					foreach($post->custom_field as $customfield){
						//IF THERE ARE ANY CUSTOMS FIELDS THAT WE ARE FILTERING BY
						if($customfield != "---NOT_SELECTED---"){
							$field_id = array_keys($post->custom_field,$customfield);
							if (intval($field_id) < 1)
								continue;
			
							$field_value = $customfield;
							if (is_array($field_value))
							{
								$field_value = implode(",", $field_value);
							}
											
							$i++;
							if ($i > 1)
							{
								$where_text .= " AND ";
								$query_text .= ',';
								
							}
							
							$where_text .= "(sorted.".intval($field_id[0])
								. " = '".Database::instance()->escape_str(trim($field_value))."')";
							
							$query_text .= 'MAX(IF(form_field_id = '.intval($field_id[0]).', form_response, NULL)) AS '.'"'.intval($field_id[0]).'"';

						}
					}
					
					// IF WE ARE FILTERING BY ANY CUSTOM FIELDS
					if ($i > 0)
					{	
						$query_text .='FROM
					  form_response
					GROUP BY
					  incident_id)';
					  
					  $sortedquery = Database::instance()->query($query_text);


						$incident_query->join('sorted','sorted.incident_id','incident.id','INNER')->where($where_text);
/*
						echo "<pre>";
						var_dump($incident_query);
						echo "</pre>";
						exit;
*/

/*
						$incident_ids = '';
						foreach ($rows as $row)
						{
							if ($incident_ids != '')
							{
									$incident_ids .= ',';
							}
		
							$incident_ids .= $row->incident_id;
						}
*/
					}
					//we have some custom fields. add them to the CSV header
					foreach($custom_forms as $field_name)
					{
						$csv_headers[] = $field_name['field_name']."-".$field_name['form_id'];
					}
					
				}
				
				//'actionable.actionable','actionable.action_taken','actionable.action_summary','actionable.action_date'
				if($actionableExists){
					$incident_query->join('actionable','actionable.incident_id','incident.id','INNER')->select('actionable.actionable','actionable.action_taken','actionable.action_summary','actionable.action_date');
					$csv_headers[] = "Actionable Status";
					$csv_headers[] = "Action Summary";
					$csv_headers[] = "Action Date";
				}
				
				//Called if actionable filter is set. 
				if(isset($post->actionable) && $post->actionable!='all'){
					if($post->actionable=='actionable'){
						$actionableQuery = "((actionable = 1 OR actionable = 2) AND action_taken = 0)";
					}
					if($post->actionable=='urgent'){
						$actionableQuery = "(actionable = 2 AND action_taken = 0)";
					}
					if($post->actionable=='action_taken'){
						$actionableQuery = "(action_taken = 1)";
					}
					if($post->actionable=='not_actionable'){
						$actionableQuery = "(actionable = 0)";
					}
	
					$incident_query->where($actionableQuery);
				}


				// Report Date Filter
				if (!empty($post->from_date) && !empty($post->to_date))
				{
					$incident_query->where(array('incident_date >=' => date("Y-m-d H:i:s", strtotime($post->from_date)), 'incident_date <=' => date("Y-m-d H:i:s", strtotime($post->to_date." 23:59:59"))));
					




				}

				$incidents = $incident_query->join('incident_category', 'incident_category.incident_id', 'incident.id', 'INNER')->orderby('incident_date', 'desc')->find_all();	
				//DUMP THE CONTENTS OF THE VAR -- THIS BREAKS IT!!!!
					
/*
						echo "<pre>";
						var_dump($custom_forms);
						var_dump($categories);
						var_dump($incidents);
						echo "</pre>";
						exit;
*/

				// CSV selected
				if ($post->formato == 0)
				{
					$report_csv .= download::arrayToCsv($csv_headers);

					foreach ($incidents as $incident)
					{
						$new_report = array();
						array_push($new_report, '"' . $incident->id . '"');
						array_push($new_report, '"' . $this->_csv_text($incident->incident_title) . '"');
						array_push($new_report, '"' . $incident->incident_date . '"');
						array_push($new_report, '"' . $this->_csv_text($incident->location->location_name) . '"');
						array_push($new_report, '"' . $this->_csv_text($incident->incident_description) . '"');

						$catstring = '"';
						$catcnt = 0;

						foreach ($incident->incident_category as $category)
						{
							if ($catcnt > 0)
							{
								$catstring .= ",";
							}
							if ($category->category->category_title)
							{
								$catstring .= $this->_csv_text($category->category->category_title);
							}
							$catcnt++;
						}

						$catstring .= '"';
						array_push($new_report, $catstring);
						array_push($new_report, '"' . $incident->location->latitude . '"');
						array_push($new_report, '"' . $incident->location->longitude . '"');

						if ($incident->incident_active)
						{
							array_push($new_report, "YES");
						}
						else
						{
							array_push($new_report, "NO");
						}

						if ($incident->incident_verified)
						{
							array_push($new_report, "YES");
						}
						else
						{
							array_push($new_report, "NO");
						}
						
						$incident_id = $incident->id;
						$custom_fields = customforms::get_custom_form_fields($incident_id, NULL, FALSE);
						if ( ! empty($custom_fields))
						{
							foreach($custom_fields as $custom_field)
							{
								array_push($new_report, '"'. $this->_csv_text($custom_field['field_response']). '"');
							}
						}
						else
						{
							foreach ($custom_forms as $custom)
							{
								array_push($new_report,'');
							}
						}
						
						if($actionableExists){
							if($incident->actionable==0) $actionableStatus = "Not Actionable";
							else if($incident->actionable==1 && $incident->action_taken==0) $actionableStatus = "Actionable";
							else if($incident->actionable==2 && $incident->action_taken==0) $actionableStatus = "Urgent";
							else if($incident->action_taken==1) $actionableStatus = "Action Taken";
							else $actionableStatus = "";
							array_push($new_report, '"' . $this->_csv_text($actionableStatus) . '"');
							array_push($new_report, '"' . $this->_csv_text($incident->action_summary) . '"');
							array_push($new_report, '"' . $incident->action_date .'"');
						}

						array_push($new_report, "\n");
						$repcnt = 0;
						foreach ($new_report as $column)
						{
							if ($repcnt > 0)
							{
								$report_csv .= ",";
							}
							$report_csv .= $column;
							$repcnt++;
						}

					}

					// Output to browser
					header("Content-type: text/x-csv");
					header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
					header("Content-Disposition: attachment; filename=" . time() . ".csv");
					header("Content-Length: " . strlen($report_csv));
					echo $report_csv;
					exit ;
				}

				// KML selected
				else
				{
					$categories = ORM::factory('category')->where('category_visible',1)->find_all();

					header("Content-Type: application/vnd.google-earth.kml+xml");
					header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
					header("Content-Disposition: attachment; filename=" . time() . ".kml");

					$view = new View("kml");
					$view->kml_name = htmlspecialchars(Kohana::config('settings.site_name'));
					$view->items = $incidents;
					$view->categories = $categories;
					$view->render(TRUE);
					exit ;
				}
			}
			// Validation errors
			else
			{
				// repopulate the form fields
				$form = arr::overwrite($form, $post->as_array());

				// populate the error fields, if any
				$errors = arr::overwrite($errors, $post->errors('download_reports'));
				$form_error = TRUE;
			}

		}

		$this->template->content->form = $form;
		$this->template->content->errors = $errors;
		$this->template->content->form_error = $form_error;

	}

	private function _csv_text($text)
	{
		$text = stripslashes(htmlspecialchars($text));
		return $text;
	}

}
