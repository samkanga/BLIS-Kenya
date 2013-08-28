<?php
#
# Returns list of patients matched with list of pending specimens
# Called via Ajax form result_entry.php
#
include("../includes/db_lib.php");
include("../includes/user_lib.php");
LangUtil::setPageId("results_entry");

$attrib_value = $_REQUEST['a'];
$attrib_type = $_REQUEST['t'];
$dynamic = 1;
$search_settings = get_lab_config_settings_search();
$rcap = $search_settings['results_per_page'];
$lab_config = LabConfig::getById($_SESSION['lab_config_id']);
$uiinfo = "op=".$_REQUEST['t']."&qr=".$_REQUEST['a'];
putUILog('result_entry_tests', $uiinfo, basename($_SERVER['REQUEST_URI'], ".php"), 'X', 'X', 'X');
?>
<?php
if(!isset($_REQUEST['result_cap']))
    $result_cap = $rcap;
else
    $result_cap = $_REQUEST['result_cap'];

if(!isset($_REQUEST['result_counter']))
    $result_counter = 1;
else
    $result_counter = $_REQUEST['result_counter'];

$query_string = "";
if($dynamic == 0)
{
    if($attrib_type == 5)
    {
            # Search by specimen aux ID
            $query_string = 
                    "SELECT s.specimen_id FROM specimen s, test t, patient p ".
                    "WHERE p.patient_id=s.patient_id ".
                    "AND s.aux_id='$attrib_value'".
                    "AND s.specimen_id=t.specimen_id ".
                    "AND t.result = '' ";
    }
    if($attrib_type == 0)
    {
            # Search by patient ID
            $query_string = 
                    "SELECT s.specimen_id FROM specimen s, test t, patient p ".
                    "WHERE p.patient_id=s.patient_id ".
                    "AND p.surr_id='$attrib_value'".
                    "AND s.specimen_id=t.specimen_id ".
                    "AND t.result = '' ";
    }
    else if($attrib_type == 1)
    {
            # Search by patient name
            $query_string = 
                    "SELECT COUNT(*) AS val FROM patient WHERE name LIKE '%$attrib_value%'";
            $record = query_associative_one($query_string);
            if($record['val'] == 0)
            {
                    # No patients found with matching name
                    ?>
                    <div class='sidetip_nopos'>
                    <b>'<?php echo $attrib_value; ?>'</b> - <?php echo LangUtil::$generalTerms['MSG_SIMILARNOTFOUND']; ?>
                    <?php
                    return;
            }
            $query_string = 
                    "SELECT s.specimen_id FROM specimen s, test t, patient p ".
                    "WHERE s.specimen_id=t.specimen_id ".
                    "AND t.result = '' ".
                    "AND s.patient_id=p.patient_id ".
                    "AND p.name LIKE '%$attrib_value%'";
    }
    else if($attrib_type == 3)
    {
            # Search by patient daily number
            $query_string = 
                    "SELECT specimen_id FROM specimen ".
                    "WHERE daily_num LIKE '%-$attrib_value' ".
                    "AND ( status_code_id=".Specimen::$STATUS_PENDING." ".
                    "OR status_code_id=".Specimen::$STATUS_REFERRED." ) ".
                    "ORDER BY date_collected DESC";
    }
}
else
{
    if($attrib_type == 5)
    {
            # Search by specimen aux ID
            $query_string = 
                    "SELECT s.specimen_id FROM specimen s, test t, patient p ".
                    "WHERE p.patient_id=s.patient_id ".
                    "AND s.aux_id='$attrib_value'".
                    "AND s.specimen_id=t.specimen_id ".
                    "AND t.result = '' LIMIT 0,$rcap ";
    }
    if($attrib_type == 0)
    {
            # Search by patient ID
            $query_string = 
                    "SELECT s.specimen_id FROM specimen s, test t, patient p ".
                    "WHERE p.patient_id=s.patient_id ".
                    "AND p.surr_id='$attrib_value'".
                    "AND s.specimen_id=t.specimen_id ".
                    "AND t.result = '' LIMIT 0,$rcap ";
    }
    else if($attrib_type == 1)
    {
            # Search by patient name
            $query_string = 
                    "SELECT COUNT(*) AS val FROM patient WHERE name LIKE '%$attrib_value%'";
            $record = query_associative_one($query_string);
            if($record['val'] == 0)
            {
                    # No patients found with matching name
                    ?>
                    <div class='sidetip_nopos'>
                    <b>'<?php echo $attrib_value; ?>'</b> - <?php echo LangUtil::$generalTerms['MSG_SIMILARNOTFOUND']; ?>
                    <?php
                    return;
            }
            $query_string = 
                    "SELECT s.specimen_id FROM specimen s, test t, patient p ".
                    "WHERE s.specimen_id=t.specimen_id ".
                    "AND t.result = '' ".
                    "AND s.patient_id=p.patient_id ".
                    "AND p.name LIKE '%$attrib_value%' LIMIT 0,$rcap";
    }
    else if($attrib_type == 3)
    {
            # Search by patient daily number
            $query_string = 
                    "SELECT specimen_id FROM specimen ".
                    "WHERE daily_num LIKE '%-$attrib_value' ".
                    "AND ( status_code_id=".Specimen::$STATUS_PENDING." ".
                    "OR status_code_id=".Specimen::$STATUS_REFERRED." ) ".
                    "ORDER BY date_collected DESC LIMIT 0,$rcap";
    } 
    else if($attrib_type == 9)
    {
            # Search by patient specimen id
                $decoded = decodeSpecimenBarcode($attrib_value);
            $query_string = 
                    "SELECT specimen_id FROM specimen ".
                    "WHERE specimen_id = $decoded[1] ".
                    "AND ( status_code_id=".Specimen::$STATUS_PENDING." ".
                    "OR status_code_id=".Specimen::$STATUS_REFERRED." ) ".
                    "ORDER BY date_collected DESC LIMIT 0,$rcap";
            
    } elseif($attrib_type == 10)
    {
            # Get all specimens with pending status
            $query_string = 
                    "SELECT *, p.name AS patient_name, st.name as specimen_name, tt.name AS test_name, tc.name AS category_name, t.status_code_id AS status
						FROM test t
						LEFT JOIN specimen s ON t.specimen_id = s.specimen_id
						LEFT JOIN patient p ON s.patient_id = p.patient_id
						LEFT JOIN specimen_type st ON s.specimen_type_id = st.specimen_type_id
						LEFT JOIN test_type tt ON t.test_type_id = tt.test_type_id
						LEFT JOIN test_category tc ON tt.test_category_id = tc.test_category_id
						LIMIT 2000";
    }
    elseif($attrib_type == 11)
    {
		    # Get all specimens that have been started with pending results
		    	$query_string =
		    	"SELECT s.specimen_id FROM specimen s, test t, patient p ".
		        "WHERE p.patient_id=s.patient_id ".
		        "AND (status_code_id=".Specimen::$STATUS_PENDING_RESULTS.") ".
		        "AND s.specimen_id=t.specimen_id ".
		        "AND t.result = ''";
	}else if($attrib_type == 12)
	{	
		# Update speciment to started status code
			$query_string = "UPDATE specimen SET status_code_id = 7 where specimen_id ='$attrib_value'";

	}
}
if($attrib_type == 12)
{
	$resultset = query_update($query_string);

} else 
	$resultset = query_associative_all($query_string, $row_count);

if(count($resultset) == 0 || $resultset == null)
{
	?>
	<div class='sidetip_nopos'>
	<?php 
	if($attrib_type == 0)
		echo " ".LangUtil::$generalTerms['PATIENT_ID']." ";
	else if($attrib_type == 1)
		echo " ".LangUtil::$generalTerms['PATIENT_NAME']." ";
	else if($attrib_type == 3)
		echo " ".LangUtil::$generalTerms['PATIENT_DAILYNUM']." ";
        if($attrib_type == 9)
        {
            echo LangUtil::$pageTerms['MSG_PENDINGNOTFOUND'];
            echo '<br>'.'Try searching by patient name';
        }
        else
        {
	echo "<b>".$attrib_value."</b>";
	echo " - ".LangUtil::$pageTerms['MSG_PENDINGNOTFOUND'];
        }
	?>
	</div>
	<?php
	return;
}
// $specimen_id_list = array();
// foreach($resultset as $record)
// {
// 	$specimen_id_list[] = $record['specimen_id'];
// }
# Remove duplicates that might come due to multiple pending tests
//$specimen_id_list = array_values(array_unique($specimen_id_list));
?>
<div class="row-fluid">
<div class="span3">Lab Section: <span id="section"></span> </div>
<div class="span3">Status: <span id="status"></span> </div>
<div class="span3">Specimen Type: <span id="specimen_type"></span> </div>
<div class="span3">Test Type: <span id="test_type"></span> </div>
</div>
<div class="clearfix"><br></div>
<table class="table table-striped table-condensed" id="<?php echo $attrib_type; ?>">
	<thead>
		<tr>
			<?php
			if($_SESSION['pid'] != 0)
			{
			?>
				<th style='width:75px;'><?php echo LangUtil::$generalTerms['PATIENT_ID']; ?></th>
			<?php
			}
			if($_SESSION['dnum'] != 0)
			{
			?>
				<th style='width:100px;'><?php echo LangUtil::$generalTerms['PATIENT_DAILYNUM']; ?></th>
			<?php
			}
			if($_SESSION['p_addl'] != 0)
			{
			?>
				<th style='width:75px;'><?php echo LangUtil::$generalTerms['ADDL_ID']; ?></th>
			<?php
			}
			//if($_SESSION['sid'] != 0)
			// "Specimen ID" now refers to aux_id
			if(false)
			{
			?>
				<th style='width:75px;'><?php echo LangUtil::$generalTerms['SPECIMEN_ID']; ?></th>
			<?php
			}
			if($_SESSION['s_addl'] != 0)
			{
			?>
				<th style='width:75px;'><?php echo LangUtil::$generalTerms['SPECIMEN_ID']; ?></th>
			<?php
			}
			//if($lab_config->hidePatientName == 0)
			if($_SESSION['user_level'] == $LIS_TECH_SHOWPNAME)
			{
			?>
				<th style='width:200px;'><?php echo LangUtil::$generalTerms['PATIENT_NAME']; ?></th>
			<?php
			}
			else
			{
			?>
			<th style='width:100px;'><?php echo LangUtil::$generalTerms['GENDER']."/".LangUtil::$generalTerms['AGE']; ?></th>
			<?php
			}
			?>
		
			<th style='width:100px;'><?php echo "Lab Section";?></th>
			<th style='width:100px;'><?php echo LangUtil::$generalTerms['SPECIMEN_TYPE']; ?></th>
			<th style='width:100px;'><?php echo LangUtil::$generalTerms['TESTS']; ?></th>
			<th style='width:100px;'><?php echo "Status";?></th>
			<th style='width:100px;'></th>
			<?php if($attrib_type==10){
			?>
			<th style='width:100px;'></th>
			<?php 
			}?>
		</tr>
	</thead>
	<tbody>
<?php
	$count = 1;
	foreach($resultset as $record)
	{
		$specimen = Specimen::getObject($record);
		$patient = Patient::getObject($record);
		$specimen_type = SpecimenType::getObject($record);
		$test = Test::getObject($record);
		$test_type = TestType::getObject($record);
		$test_category = TestCategory::getObject($record);
		?>
		<tr <?php
		if($attrib_type == 3 && $count != 1)
		{
			# Fetching by patient daily number. Hide all records except the latest one
			echo " class='old_pnum_records' style='display:none' ";
		}
		?> id="<?php echo $specimen->specimenId; ?>">
			<?php
			if($_SESSION['pid'] != 0)
			{
			?>
				<td style='width:75px;'><?php echo $patient->getSurrogateId(); ?></td>
			<?php
			}
			if($_SESSION['dnum'] != 0)
			{
			?>
				<td style='width:100px;'><?php echo $specimen->getDailyNumFull(); ?></td>
			<?php
			}
			if($_SESSION['p_addl'] != 0)
			{
			?>
				<td style='width:75px;'><?php echo $patient->getAddlId(); ?></td>
			<?php
			}
			//if($_SESSION['sid'] != 0)
			// "Specimen ID" now refers to aux_id
			if(false)
			{
			?>
				<td style='width:75px;'><?php echo $specimen->specimenId; ?></td>
			<?php
			}
			if($_SESSION['s_addl'] != 0)
			{
			?>
				<td style='width:75px;'><?php echo $specimen->getAuxId(); ?></td>
			<?php
			}
			//if($lab_config->hidePatientName == 0)
			if($_SESSION['user_level'] == $LIS_TECH_SHOWPNAME)
			{
			?>
				<td style='width:200px;'><?php echo $patient->getPatientName()." (".$patient->sex." ".$patient->getAgeNumber().") "; ?></td>
			<?php
			}
			else
			{
			?>
				<td style='width:100px;'><?php echo $patient->sex."/".$patient->getAgeNumber(); ?></td>
			<?php
			}
			?>
			<td style='width:100px;'><?php echo $test_category->getCategoryName(); ?></td>
			<td style='width:100px;'><?php echo $specimen_type->getSpecimenName(); ?></td>
			<td style='width:100px;'>
			<?php
			echo $test_type->getTestName();
			?>
			</td>
			<?php $status = $test->getStatusCode();
			
			echo '<td class="hidden-phone"><span class="label ';
			
			if($status == Specimen::$STATUS_PENDING){
				echo 'label-important">Pending';
				echo '</span></td>';
				echo '
			<td style="width:100px;"><a href="javascript:start_test('.$quote.$specimen->specimenId.$quote.');" title="Click to begin testing this Specimen" class="btn red mini">
				<i class="icon-ok"></i>'.LangUtil::$generalTerms['START_TEST'].'</a>
			</td>
			<td style="width:100px;"><a href="javascript:fetch_specimen2('.$quote.$specimen->specimenId.$quote.');" title="Assign Specimen to a technician" class="btn mini">
				<i class="icon-group"></i>'.LangUtil::$generalTerms['ASSIGN_TO'].'</a>
			</td>';
			}else
			if($status == Specimen::$STATUS_DONE){
				echo 'label-success">Completed';
				echo '</span></td>';
				echo '
			<td style="width:100px;"><a href="javascript:start_test('.$quote.$specimen->specimenId.$quote.');" title="Click to begin testing this Specimen" class="btn red mini">
				<i class="icon-ok"></i>'.LangUtil::$generalTerms['START_TEST'].'</a>
			</td>
			<td style="width:100px;"><a href="javascript:fetch_specimen2('.$quote.$specimen->specimenId.$quote.');" title="Assign Specimen to a technician" class="btn blue mini">
				<i class="icon-group"></i>'.LangUtil::$generalTerms['ASSIGN_TO'].'</a>
			</td>';
			}else
			if($status == Specimen::$STATUS_REFERRED){
				echo 'label-warning">Referred';
				echo '</span></td>';
				echo '
			<td style="width:100px;"><a href="javascript:start_test('.$quote.$specimen->specimenId.$quote.');" title="Click to begin testing this Specimen" class="btn red mini">
				<i class="icon-ok"></i>'.LangUtil::$generalTerms['START_TEST'].'</a>
			</td>
			<td style="width:100px;"><a href="javascript:fetch_specimen2('.$quote.$specimen->specimenId.$quote.');" title="Assign Specimen to a technician" class="btn blue mini">
				<i class="icon-group"></i>'.LangUtil::$generalTerms['ASSIGN_TO'].'</a>
			</td>';
			}else
			if($status == Specimen::$STATUS_TOVERIFY){
				echo 'label-info">Not Verified';
				echo '</span></td>';
				echo '
			<td style="width:100px;"><a href=";" title="Click to begin testing this Specimen" class="btn mini">
				<i class="icon-search"></i>View Results</a>
			</td>
			<td style="width:100px;"><a href="javascript:fetch_specimen2('.$quote.$specimen->specimenId.$quote.');" title="Verify test" class="btn green mini">
				<i class="icon-ok"></i>Verify</a>
			</td>';
			}else
			if($status == Specimen::$STATUS_REPORTED){
				echo 'label-success">Reported';
				echo '</span></td>';
				echo '
			<td style="width:100px;"><a href="javascript:start_test('.$quote.$specimen->specimenId.$quote.');" title="Click to begin testing this Specimen" class="btn red mini">
				<i class="icon-ok"></i>'.LangUtil::$generalTerms['START_TEST'].'</a>
			</td>
			<td style="width:100px;"><a href="javascript:fetch_specimen2('.$quote.$specimen->specimenId.$quote.');" title="Assign Specimen to a technician" class="btn blue mini">
				<i class="icon-group"></i>'.LangUtil::$generalTerms['ASSIGN_TO'].'</a>
			</td>';
			}else
			if($status == Specimen::$STATUS_RETURNED){
				echo 'label-info warning">Returned';
				echo '</span></td>';
				echo '
			<td style="width:100px;"><a href="javascript:start_test('.$quote.$specimen->specimenId.$quote.');" title="Click to begin testing this Specimen" class="btn red mini">
				<i class="icon-ok"></i>'.LangUtil::$generalTerms['START_TEST'].'</a>
			</td>
			<td style="width:100px;"><a href="javascript:fetch_specimen2('.$quote.$specimen->specimenId.$quote.');" title="Assign Specimen to a technician" class="btn blue mini">
				<i class="icon-group"></i>'.LangUtil::$generalTerms['ASSIGN_TO'].'</a>
			</td>';
			}else
			if($status == Specimen::$STATUS_REJECTED){
				echo 'label-inverse">Rejected';
				echo '</span></td>';
				echo '
			<td style="width:100px;"><a href="javascript:start_test('.$quote.$specimen->specimenId.$quote.');" title="Click to begin testing this Specimen" class="btn red mini">
				<i class="icon-ok"></i>'.LangUtil::$generalTerms['START_TEST'].'</a>
			</td>
			<td style="width:100px;"><a href="javascript:fetch_specimen2('.$quote.$specimen->specimenId.$quote.');" title="Assign Specimen to a technician" class="btn mini">
				<i class="icon-group"></i>'.LangUtil::$generalTerms['ASSIGN_TO'].'</a>
			</td>';
			}else
			if($status == Specimen::$STATUS_PENDING_RESULTS){
				echo 'label-warning">Pending Results';
				echo '</span></td>';
				echo '
			<td style="width:100px;"><a href="javascript:fetch_specimen2('.$quote.$specimen->specimenId.$quote.');" title="Click to Enter Results for this Specimen" class="btn yellow mini">
				<i class="icon-ok"></i>Enter Results</a>
			</td>
			<td style="width:100px;"><a href="javascript:fetch_specimen2('.$quote.$specimen->specimenId.$quote.');" title="View test details" class="btn mini">
				<i class="icon-search"></i>View Details</a>
			</td>';
			}else{
				echo '';
			}
			
			?>
		</tr>
		
		<div class='result_form_pane' id='result_form_pane_<?php echo $specimen->specimenId; ?>'>
		</div>
		<?php
		$count++;
	}
	?>
	</tbody>
</table>
<?php
if($attrib_type == 3 && $count > 2)
{
	# Show "view more" link for revealing earlier patient records
	?>
	<a href='javascript:show_more_pnum();' id='show_more_pnum_link'><small>View older entries &raquo;</small></a>
	<br><br>
	<?php
}

?>