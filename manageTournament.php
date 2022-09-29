<?php
  require_once 'includes/dbConnnection.php';
  require_once 'includes/header.php';
  require_once 'classes/allClasses.php';
  require_once 'includes/fixtureFunction.php';
  require_once 'includes/preparedStatementsFunction.php';
  require_once 'processing/process.php';

  //get player info
  $playersName = GetPlayerNames($conn);
  ?>
  <div class="createTournamentDiv">
  <?php
  if($game_typeArray == null){
    ?><h3 class="bMessage"> * ERROR RETRIEVING GAME TYPES</h3>
     <div class="buttonsDiv"><?php
  }
  else if($playersName == null){
    ?><h3 class="bMessage"> * ERROR RETRIEVING PLAYER NAMES</h3>
    <div class="buttonsDiv"><?php
  }
  else{
    switch($_GET['processType']){
      case 'editFutureGame':
        ?>
        <div class="badMessage <?= (isset($errorArr) && sizeof($errorArr)>0)? 'showError' : '' ?>">
        <h1>WARNING!!!</h1>
        <h3>Before proceding, Please make sure:</h3>
        <ul>
        <?php
        if(isset($errorArr)){
          foreach($errorArr as $message){echo "<li>".$message."</li>"; }
        }?>
        </ul>
        </div>
        <h3 class="HEADERS">ENTER GAME DETAILS</h3>
          <form class="form" action="" method="post">
            <span class = "bMessageReq">* Required field.</span>
            <br>
            <label class="createLabel bMessage" >  
              Game Name: 
              <input type="text" name="gameName" placeholder="Enter game name.."  class="gameName" size = "24" value ="<?php echo isset($name) ? $name : $gameArray['Name']?>" required/>
            </label>
          <label class="createLabel bMessage" > 
              Game Type:
              <select name="selectGameType" class="selectGameType" required>
               <option disabled selected value> -- Select an option -- </option>
                <?php 
                  foreach($game_typeArray as $gameSetupTypeDetails ){
                    if($gameSetupTypeDetails["ID"] == $selectedGameType ){
                      echo "<option  data-settings='".$siteSettingsJson."'  ".((isset($game_typeID) ? ($game_typeID == $gameSetupTypeDetails['ID'] ? "selected" : "") : ((isset($selectedGameType) && ($selectedGameType == $gameSetupTypeDetails['ID']) ? 'selected' : ''))))."  value=".$gameSetupTypeDetails['ID']." >".$gameSetupTypeDetails['NameType']."</option>";
                    }
                    else{
                      echo "<option  data-settings='".$gameSetupTypeDetails["GameTypeSettings"]."'  ".((isset($game_typeID) ? ($game_typeID == $gameSetupTypeDetails['ID'] ? "selected" : "") : ((isset($selectedGameType) && ($selectedGameType == $gameSetupTypeDetails['ID']) ? 'selected' : ''))))."  value=".$gameSetupTypeDetails['ID']." >".$gameSetupTypeDetails['NameType']."</option>";
                    }
                  }
                ?>
              </select>
          </label> <div id="hiddenInfo" class="settings" ></div>
          <div class="settings"  id="hiddenInfo" style='display:none;'></div>
          <label class="createLabel bMessage" > 
              Start Date:
                <input type="date" class="startdate" name="startDate" value="<?php echo isset($startDate) ? $startDate : $gameArray['StartDate'];?>" required  /> 
          </label>
          <label class="createLabel bMessage" > 
              End Date:
              <input type="date" class="enddate" name="endDate" value="<?php echo isset($endDate) ? $endDate : $gameArray['EndDate'];?>" required /> 
            </label>
          <div class="playerSelection">
          <div class="selectPlayerSpan bMessageDiv">ADD /  REMOVE PLAYERS  <i class="fa fa-arrow-down"></i></div>
          <br>
          <div class="select">
          <div class="playerDisplay">
              <span class="playerDisplay1">Select All Players
                <span class='checkbox'>
                  <input  type='checkbox' class="selAll" checked />
                </span>
              </span>
            </div>
          <div class="playerDisplay">
          <?php
            foreach($playersName as $player){
              echo "<span class='playerDisplay1'>". $player['Name']." ".$player['Lastname']."
                      <span class='checkbox'>
                          <input id='".$player['ID']."' type='checkbox' name='checkedPlayer[]'  class='checkbox'  value='".$player['ID']."' ".((isset($selectedP) &&(in_array($player['ID'],$selectedP))) ? "checked" : '')."/>
                      </span>
                    </span>";
            }
            ?>
          </div><br>
          </form>
          </div>
          <div class="buttonsDiv">
              <input type="submit" class ="editGameName" name="editGame1" value="Edit Future Game" />
              <input type="hidden"  name="processType" value="editFutureGames" />
          <?php
      break;
      
      case 'createTournament':
        ?>
        <div class="badMessage <?= (isset($errorArr) && sizeof($errorArr)>0)? 'showError' : '' ?>">
        <h1>WARNING!!!</h1>
          <h3>Before proceding, Please make sure:</h3>
        <ul>
        <?php
        if(isset($errorArr)){
          foreach($errorArr as $message){echo "<li>".$message."</li>"; }
        }?>
        </ul>
        </div>
        <h3 class="HEADERS">ENTER GAME DETAILS</h3>
        <form name ="myForm" class="form" method="post">
            <span class = "bMessageReq">* Required field.</span>
            <br>
            <label class="createLabel bMessage" >   
              Game Name: 
              <input type="text" name="gameName" placeholder="Enter game name.."  class="gameName bMessage" size = "24" value="<?php echo isset($_POST['gameName']) ? $_POST["gameName"] : '';?>" required />
            </label>
          <label  class="createLabel bMessage" > 
          Game Type:
              <select name="selectGameType" class="selectGameType bMessage" required >
              <option disabled selected value> -- Select an option -- </option>
                <?php 
                  foreach($game_typeArray as $game_type){
                    echo "<option  data-settings='".$game_type["GameTypeSettings"]."'  ".(isset($game_typeID) && $game_typeID == $game_type['ID'] ? 'selected' : '' )."  value=".$game_type['ID']." >".$game_type['NameType']."</option>";
                  }
                ?>
              </select>
          </label>
          <div id="hiddenInfo" class="settings" ></div>
          <label  class="createLabel bMessage" >
              Start Date:
                <input type="date" class="startdate" name="startDate" value="<?php echo isset($_POST['startDate']) ? $_POST["startDate"] : '';?>" required /> 
              </label>
          <label  class="createLabel bMessage">
              End Date:
              <input type="date" class="enddate " name="endDate" value="<?php echo isset($_POST['endDate']) ? $_POST["endDate"] : '';?>" required /> 
            </label>
          <div class="playerSelection ">
          <div class="selectPlayerSpan bMessageDiv">ADD /  REMOVE PLAYERS <i class="fa fa-arrow-down"></i></div>
          <br>
          <div class="select">
            <div class="playerDisplay">
              <span class="playerDisplay1">Select All Players
                <span class='checkbox'>
                  <input  type='checkbox' class="selAll" checked />
                </span>
              </span>
            </div>
          <div class="playerDisplay">
          <?php
            foreach($playersName as $player){
              echo "<span class='playerDisplay1'>". $player['Name']." ".$player['Lastname']."
                      <span class='checkbox'>
                          <input id='".$player['ID']."' type='checkbox' name='checkedPlayer[]'  class='checkbox'  value='".$player['ID']."' ".((!isset($selectedPlayers) || (in_array($player['ID'],$selectedPlayers))) ? "checked " : '')."/>
                      </span></span>";
            }
            ?>
          </div><br>
        </div>
          </div>
        <div class="buttonsDiv">
              <input type="submit" class ="buttonStart" name="submit" value="Create New Game" />
              <input type="hidden"  name="processType" value="createTournament" />
        </form>
        <?php
      break;
    }
  }
  ?><a id='backIndex' class="backIndex" href="index.php">Main Menu</a></div>
  <br><br>
  <?php require 'includes/footer.php';?>
  </div>
  </div>
  </body>
  </html>

