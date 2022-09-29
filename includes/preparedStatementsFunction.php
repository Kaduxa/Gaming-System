<?php

  // in order to be sure we will see every error in the script
  /*** THIS! ***/
  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

  //validate the info posted by the user,passing $errorArr by reference
  function InfoValidation($conn, $game_typeID, $name, $endDate, $startDate, $selectedPlayers, &$errorArr, $date){
    //check if game type is set
    if($game_typeID === null){
      array_push($errorArr, "THE GAME TYPE IS SET");
    }
    //check name length
    if(strlen($name) > 50){
      array_push($errorArr, "THE GAME NAME IS LESS THAN 50 CHARACTERS");
    }
    //check if players are selected
    if($selectedPlayers === null){
      array_push($errorArr, "PLAYERS ARE SELECTED");
    }
    // //checks for end/start date if is set in the past
    // if($startDate < $date){
    //   array_push($errorArr, "THE START DATE IS NOT SET IN THE PAST");
    // }
    //checks for end/start date if is set in the past
    if($endDate < $date){
      array_push($errorArr, "THE END DATE IS NOT SET IN THE PAST");
    }
  }

  //validate the gameTypeSetting values,passing $gameTypeSetting and $errorArr by reference
  function ValidateGameTypeSettings($conn,&$gameTypeSetting,&$errorArr){
    foreach($gameTypeSetting as  $fieldName => $jsonSettings ){
      if($fieldName === "rules"){
        continue;
      }
      $value = $_POST[$fieldName];
      
      $gameTypeSetting[$fieldName]["enteredValue"] = $value;
      
      foreach($jsonSettings["attributes"] as $attr => $val){
        switch($attr){
          case 'required':
            if(($gameTypeSetting[$fieldName] ?? "" ) === "" && $val===true){
                    array_push($errorArr, "THE GAME HAS AT LEAST ONE '".$fieldName."'");
            }
          break;

          case "min":
            if($_POST[$fieldName] < $val){
              array_push($errorArr, "THE GAME '".$fieldName."' IS SET AS '".$val."' OR MORE");
            }
          break;

          case "step":
            $step = $val;
            if(($value != null && fmod_c($value,$step)!= 0)){
              array_push($errorArr, "THE VALUE IS A VALID INTEGER");
            }
          break;
        }
      }
    }

    foreach(($gameTypeSetting["rules"] ?? array()) as $individualRule){
      foreach($individualRule as $ruleName => $ruleDetails){
        switch($ruleName){
          case "checkLessThan":
            if($_POST[$ruleDetails[0]] > $_POST[$ruleDetails[1]]){
              array_push($errorArr, "THE MIN RANGE IS NOT BIGGER THAN MAX RANGE");
            }
        }
      }
    }
  }

  //get all the games that been created 
  function GetAllGames($conn){
    try{
      $allGames = [];
      $st = $conn->prepare("SELECT g.`ID`,g.`Name`,g.`TypeID`,g.`StartDate`,g.`EndDate`,g.`Active`
      FROM `game` `g` WHERE `g`.`Active` = 1");
      $st->execute();
      $result = $st->get_result();
      while($row = $result->fetch_assoc()) {
        $allGames[] = $row;
      }
      return $allGames;
      $st->close();
    }
    catch (Exception $e) {
      $allGames = null;
      return $allGames;
    }
  }

  //get the competition leader
  function CompetitionLeaders($conn,$gameID){
    //gets the class name of  the game
    $typeIDClass = GameTypeClasses($conn,$gameID);
    //checks for the winner of the competitions
    $competitionWinnerID = $typeIDClass::GetCompetitionLeader($conn,$gameID);

    //adding the competition winner into competition winner table
    try{
      $stmt = $conn->prepare("INSERT INTO `competition_winners` (`GameID`, `WinnerID`) VALUES (?, ?)");
      $stmt->bind_param("ii", $gameID, $competitionWinnerID);
      $stmt->execute();
      $stmt->close();
    }catch(Exception $e){
      return false;
    }

    //setting active to 0
    try{
      $stmt = $conn->prepare("UPDATE `game` SET `Active` = 0 WHERE `ID` = ?");
      $stmt->bind_param("i", $gameID);
      $stmt->execute();
      $stmt->close();
    }catch(Exception $e){
      return false;
    }

    return true;
  }

  //get the game class type
  function GameTypeClasses($conn,$gameID){
    try{
      $stmt = $conn->prepare("SELECT gt.`Classes` FROM game g
        INNER JOIN game_type  gt ON g.`TypeID` = gt.`ID` WHERE g.`ID`=?;");
      $stmt->bind_param("i", $gameID);
      $stmt->execute();
      $stmt->store_result();
      $stmt->bind_result($classes);
      $stmt->fetch();
      $stmt->close();
      return $classes;
    }catch(Exception $e){
      return false;
    }
  }

  //get the current active games
  function ActiveGames ($conn){
    try{
        //get the active games 
        $activeGamesArray = [];
        $stmt = $conn->prepare("SELECT g.`ID`AS `Game ID`, g.`Name`,g.`StartDate`,g.`EndDate`,gt.`ID` AS `TypeID`,gt.`NameType`
                                FROM `game` g
                                INNER JOIN game_type gt ON g.`TypeID` = gt.`ID`
                                WHERE CURDATE() <= g.`Enddate` AND CURDATE() >= g.`StartDate` AND g.`Active`= 1");
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()) {
          $activeGamesArray[] = $row;
        }
        return $activeGamesArray;
        $stmt->close();
    }catch(Exception $e){
      $activeGamesArray = null;
      return $activeGamesArray;
    }
  }

  //get the future games
  function FutureGames($conn){
   try{
      //get data from future games 
      $futureGames = [];
      $stm1 = $conn->prepare("SELECT g.`ID` AS `Game ID`, g.`Name`,g.`StartDate`,g.`EndDate`,gt.`ID`,gt.`NameType`,g.`SelectedPlayers`
                              FROM `game` g
                              INNER JOIN game_type gt ON g.`TypeID` = gt.`ID`
                              WHERE CURDATE() <= g.`Enddate` AND CURDATE() < g.`StartDate`;");
      $stm1 ->execute();
      $result = $stm1->get_result();
      while($row = $result->fetch_assoc()){
          $futureGames[] = $row;
      }
      return $futureGames;
      $stm1->close();
    }catch(Exception $e){
      $futureGames = null;
      return $futureGames;
    }
  }

  //get all tournament winners
  function GetCompetitionWinners($conn){
    try{
      //get the competition winners
      $competitionWinners = [];
      $stmt = $conn->prepare("SELECT `g`.ID,`g`.`Name` AS `Game Name`,`g`.`StartDate`,`g`.`EndDate` AS `End Date`,CONCAT(`p`.`Name`, ' ', `p`.`Lastname`) AS `Winner`,`g`.`TypeID`,gt.`NameType`
                              FROM `competition_winners` `tw`
                              INNER JOIN `game_players` `p` ON `p`.`ID`= `tw`.`WinnerID`
                              INNER JOIN `game` `g` ON `g`.`ID` = `tw`.`GameID`
                              INNER JOIN `game_type` `gt` ON `gt`.`ID`= `g`.`TypeID`
                              ORDER BY g.`EndDate` ASC ;");
      $stmt->execute();
      $result = $stmt->get_result();
      while($row = $result->fetch_assoc()) {
        $competitionWinners[] = $row;
      }
      return $competitionWinners;
      $stmt->close();
    }catch(Exception $e){
      $competitionWinners = null;
      return $competitionWinners;
    }
  }

  //get the game class name
  function GetTypeIDClasse($conn, $game_typeID){
    try{
      $stmt = $conn->prepare("SELECT gt.`Classes` FROM game_type  gt  WHERE gt.`ID`=?;");
      $stmt->bind_param("i", $game_typeID);
      $stmt->execute();
      $stmt->store_result();
      $stmt->bind_result($game_typeClass);
      $stmt->fetch();
      $stmt->close();
      return $game_typeClass;
    }catch(Exception $e){
      return false;
    }
  }

  //get the game type 
  function GameType($conn){
    try{
        //get all info from game_type table
        $game_typeArray = [];
        $stmt = $conn->prepare("SELECT * FROM `game_type`;");
        $stmt->execute();
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()){
          $game_typeArray[] = $row;
        }
        return $game_typeArray;
        $stmt->close();
    }
    catch(Exception $e){
      $game_typeArray = null;
      return $game_typeArray;
    }
  }

  //get the selected game type siteSettings to change
  function GetSiteSettingGameType($conn,$editGameID){
    try{
      //get all info from game_type table
      $stmt = $conn->prepare("SELECT gt.`GameTypeSettings`
        FROM `game_type` gt
        INNER JOIN `game` g ON  gt.`ID`= g.`TypeID`
        WHERE g.`ID`=?;");
      $stmt->bind_param("i",$editGameID);
      $stmt->execute();
      $stmt->store_result();
      $stmt->bind_result($siteSetting);
      $stmt->fetch();
      return $siteSetting;
      $stmt->close();
    }catch(Exception $e){
      $siteSetting = null;
      return  $siteSetting;
    }
  }
  
  //get the selected game name to change
  function GetGameName($conn, $editGameID){
    try{
      //get the data from the selected game to edit
     $stmt = $conn->prepare("SELECT * FROM `game` WHERE `ID` = ?;");
     $stmt->bind_param("i",$editGameID);
     $stmt->execute();
     $result = $stmt->get_result();
     while($row = $result->fetch_assoc()){
       $gameArray = $row;
     }
     return $gameArray;
     $stmt->close();
    }catch(Exception $e){
     $gameArray = null;
     return $gameArray;
    }
  }

  //get all game information
  function GetGameInfo($conn, $gameID){
    try{
      //get game info
      $gameInfo = [];
      $stmt = $conn->prepare("SELECT `g`.`ID`,g.`Name`,g.`TypeID`, g.`StartDate`,g.`EndDate`,g.`Active`,g.`siteSettings`,g.`SelectedPlayers`,gt.`NameType`
      FROM `game` g INNER JOIN game_type gt ON `g`.`TypeID`= `gt`.`ID` WHERE `g`.`ID` = ?;");
      $stmt->bind_param("i", $gameID);
      $stmt->execute();
      $result = $stmt->get_result();
      while($row = $result->fetch_assoc()) {
        $gameInfo = $row;
      }
      return $gameInfo;
      $stmt->close();
    }catch(Exception $e){
      $gameInfo = null;
      return $gameInfo;
    }
  }

  //get players names
  function GetPlayerNames($conn){
    try{
      //get all info from game_players table
      $selectedPlayerArray = [];
      $stmt1 = $conn->prepare("SELECT * FROM `game_players` where `ID` > 0 AND `Status` = 1 order by `Name` ASC;");
      $stmt1->execute();
      $result = $stmt1->get_result();
      while($row = $result->fetch_assoc()){
        $selectedPlayerArray[] = $row;
      }
      return $selectedPlayerArray;
      $stmt1->close();
    }catch(Exception $e){
      $selectedPlayerArray = null;
      return $selectedPlayerArray;
    }
  }

  //get game type settings
  function GetGameTypeSettings($conn,$game_typeID){
    try{
      //get the active games 
      $stmt = $conn->prepare("SELECT `GameTypeSettings` FROM `game_type` WHERE `ID` = ?");
      $stmt->bind_param("i",$game_typeID);
      $stmt->execute();
      $stmt->store_result();
      $stmt->bind_result($gameTypeSettings);
      $stmt->fetch();
      return $gameTypeSettings;
      $stmt->close();
    }catch(Exception $e){
      $gameTypeSettings = null;
      return $gameTypeSettings;
    }
  }

  //get the game settings
  function GetGameSiteSetting($conn,$editGameID){
    try{
      //get all info from game_type table
      $stmt = $conn->prepare("SELECT `siteSettings`
        FROM `game` WHERE `ID`=?;");
      $stmt->bind_param("i",$editGameID);
      $stmt->execute();
      $stmt->store_result();
      $stmt->bind_result($gameSiteSetting);
      $stmt->fetch();
      return $gameSiteSetting;
      $stmt->close();
    }catch(Exception $e){
      $gameSiteSetting = null;
      return  $gameSiteSetting;
    }

  }