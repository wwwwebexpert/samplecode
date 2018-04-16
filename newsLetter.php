<?php

function compose($agentId, $cast) {

  include_once("webapps/classes/DAO.php");  
  $db = new DAO();
  $dbConn = $db -> getConnection();
 
  $sql = "SELECT LEAD.FIRST_NAME, LEAD.LAST_NAME, LEAD.EMAIL " .
		  "FROM LEAD " .
		  "WHERE LEAD.EMAIL != '' " .
		  "AND AGENT_ID = $agentId ";

  $rs      = mysql_query($sql, $dbConn) or die(mysql_error());    
  $rsAssoc = mysql_fetch_assoc($rs);

  $sql = "SELECT USERS.GROUP_1, USERS.GROUP_2, USERS.GROUP_3, " .
		  "USERS.GROUP_4, USERS.GROUP_5, USERS.GROUP_6 " .
		  "FROM USERS WHERE USER_ID = $agentId";

  $rs      = mysql_query($sql, $dbConn) or die(mysql_error());    
  $rsAssoc2 = mysql_fetch_assoc($rs);
   
  mysql_free_result($rs);
  $db -> closeConnection();  
  
  if ($rsAssoc['REMINDER'] == "Y") { ?>  
  <script type="text/javascript">doWhat = "hide";</script>
<?php } ?>  
<script>
	window.name = "LeadMonitor";
	</script>
  <input type="hidden" name="LEAD_NOTE_ID" value="<?php echo($noteId); ?>" />
  <input type="hidden" name="LEAD_ID" value="<?php echo($leadId); ?>" />
  <table width="70%" cellpadding="2" cellspacing="0" border="0" align="center" class="formTable">
    <thead>
      <tr>
        <th align="left" colspan="4">&nbsp;
		<a href="newsLetter.php?">Show All</a>&nbsp;|&nbsp;
		<a href="newsLetter.php?group=1"><?php echo $rsAssoc2['GROUP_1']; ?></a>&nbsp;|&nbsp;
		<a href="newsLetter.php?group=2"><?php echo $rsAssoc2['GROUP_2']; ?></a>&nbsp;|&nbsp;
		<a href="newsLetter.php?group=3"><?php echo $rsAssoc2['GROUP_3']; ?></a>&nbsp;|&nbsp;
		<a href="newsLetter.php?group=4"><?php echo $rsAssoc2['GROUP_4']; ?></a>&nbsp;|&nbsp;
		<a href="newsLetter.php?group=5"><?php echo $rsAssoc2['GROUP_5']; ?></a>&nbsp;|&nbsp;
		<a href="newsLetter.php?group=6"><?php echo $rsAssoc2['GROUP_6']; ?></a>&nbsp;&nbsp;&nbsp;&nbsp;|
		<a href="newsLetter.php?leased_client=1">Leased Clients</a>&nbsp;&nbsp;&nbsp;&nbsp;|
		<a href="newsLetter.php?ref_agent=1">Referral Agents</a>
		<?php 
			if($cast == 'S' || $cast == 'M') {
		?>
    &nbsp;|&nbsp;<a href="newsLetter.php?active_agents=1SourceApartments">1SourceApartments</a>
    &nbsp;|&nbsp;<a href="newsLetter.php?active_agents=1SourceRealtyGroup">1SourceRealtyGroup</a>			
		<div align="right">
			<a href="newsLetter.php?group=<?php echo $group; ?>&leadType=H">Home</a>&nbsp;|&nbsp;
			<a href="newsLetter.php?group=<?php echo $group; ?>&leadType=A">Apartment</a>&nbsp;|&nbsp;
      <a href="newsLetter.php?properties">Properties</a>

		</div>
		<?php
			}
		?>
        </th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td width="25%" class="borderRight" align="right" style="color: #666699;">
          Email List:
        </td>
        <td width="75%">
                                <?php
  include_once("webapps/functions/userEmailDropDown.php");
  if(isset($_GET['ref_agent']))
  {
  userEmailDropDownRefAgent(true);
  }
  elseif(isset($_GET['leased_client'])){
  
	 listingUserEmailDropDown('R',true);
  }elseif(isset($_GET['properties'])){ // New Functiobality As asked by client

    $dbConn = $db -> getConnection();
    $query = "SELECT APARTMENT_NAME, APARTMENT_EMAIL FROM PROPERTY WHERE APARTMENT_EMAIL != ''";
    $result  = mysql_query($query, $dbConn) or die(mysql_error());
    echo '<select class="input" multiple="" size="25" name="LEAD_ID[]">';
    echo '<option value="">Select From List</option>';
    while ($emailList = mysql_fetch_assoc($result)) {
    echo '<option value="'.$emailList["APARTMENT_NAME"].' <'.$emailList["APARTMENT_EMAIL"].'>">'.$emailList["APARTMENT_NAME"].'  ['.$emailList["APARTMENT_EMAIL"].'] </option>';
    }
    echo '</select>';
    $db -> closeConnection(); 

  }elseif(isset($_GET['active_agents'])){
    $dbConn = $db -> getConnection();

    if($_GET['active_agents']=='1SourceApartments'){
      $company_id = 1;
    }elseif ($_GET['active_agents']=='1SourceRealtyGroup') {
      $company_id = 4;
    }

    $query = "SELECT * FROM `USERS` WHERE `COMPANY_ID` = ".$company_id." AND `CAST`='A' AND `EMAIL` != '' ";

    $result  = mysql_query($query, $dbConn) or die(mysql_error());
    echo '<select class="input" multiple="" size="25" name="LEAD_ID[]">';
    echo '<option value="">Select From List</option>';
    while ($emailList = mysql_fetch_assoc($result)) {
    echo '<option value="'.$emailList["FIRST_NAME"].' <'.$emailList["EMAIL"].'>">'.$emailList["FIRST_NAME"].'  ['.$emailList["EMAIL"].'] </option>';
    }
    echo '</select>';
    $db -> closeConnection(); 
  }
  else{
  
  userEmailDropDown($agentId,
                $cast,
				true);
  }
?> 

        </td>
      </tr>
      <tr>
        <td class="borderRight" align="right" style="color: #666699;">
          Subject:
        </td>
        <td>
          <input type="text" name="subject" size="50">
        </td>
      </tr>
	  <tr>
        <td width="25%" class="borderRight" align="right" style="color: #666699;">
          Template List:
        </td>
        <td width="75%">
<?php
  include_once("webapps/functions/templateDropDown.php");
  templateDropDown($agentId,
                $cast,
				false);
?> 			
        </td>
	  
	  </tr>          
      <tr>
        <td colspan="2" align="center" id="dateTd" style="">&nbsp;
			
        </td>
      </tr>      
    </tbody>           
    <tfoot>
      <tr>
        <td align="center" colspan="4" height="40">
		 <input name="button" type="button" value="Add Template" onclick="javascript:popUp('addTemplate.php', 'addTemplate', '640','350');" class="button" /> 
		  &nbsp;&nbsp;
          <input name="button" type="button" value="Edit Templates" class="button" onclick="edit();"/>
          &nbsp;&nbsp;
		  <input name="button" type="button" value="Preview" onclick="preview()" class="button" /> 
		  &nbsp;&nbsp;
          <input name="submit" type="submit" value="Send" class="button" />
        </td>
      </tr>
    </tfoot>
  </table>
<?php  
} // end of function
?>