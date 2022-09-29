<?php

  $connection = new DBConnection();
  $conn = $connection->getConnection();

  //get all info from game_type table
  $game_typeArray = GameType($conn);

  /*
    returns the floating point remainder of the division
    (entered value. e.g- if value is integer)
  */
  function fmod_c($value, $step){
    return $value - floor($value/$step) * $step;
  }

  //get all the games
  $allGames = GetAllGames($conn);
  $today = date("Y-m-d");
  foreach($allGames as $games){
    //checks if the end date has passed
    if($games['EndDate'] < $today){
      //checks for the leader of the competition
      $competitionLeader = CompetitionLeaders($conn,$games['ID']);
    }
  }

  switch($_POST['processType']  ?? "unknown"){
    //create tournamnet
    case "createTournament":
      $date = date("Y-m-d");
      $errorArr = [];
      $selectedPlayers = isset($_POST['checkedPlayer']) ? $_POST['checkedPlayer'] : null ;
      $gameName = $_POST['gameName'] === '' ? array_push($errorArr, "THE GAME HAS A NAME") : $_POST["gameName"];
      $startDate = $_POST['startDate'] === '' ?  array_push($errorArr, "THE START DATE ARE SET") : $_POST["startDate"];
      $endDate = $_POST["endDate"]  === '' ?  array_push($errorArr, "THE END DATE ARE SET") : $_POST["endDate"];
      $game_typeID = $_POST["selectGameType"];

      //validate the info posted by the user,passing $errorArr by reference
      $resultValidation = InfoValidation($conn,$game_typeID,$gameName,$endDate,$startDate,$selectedPlayers,$errorArr,$date);
      
      //get the settings for the game_type
      $gameTypeSettings = GetGameTypeSettings($conn,$game_typeID);
      $gameTypeSetting = json_decode($gameTypeSettings,true);
      // $updateGameTypeSettings = json_encode($gameTypeSetting);

      //check if the gameTypeSettings is set
      if($gameTypeSetting > 0){
        //validate the gameTypeSetting values,passing $gameTypeSetting and $errorArr by reference
        $resultValidation = ValidateGameTypeSettings($conn,$gameTypeSetting,$errorArr);

        $updatedGameTypeSettings = json_encode($gameTypeSetting);
        //update the values entered into gameTypeSettings column
        foreach($game_typeArray as $k=>&$v){
          if($v['ID'] == $game_typeID){
            $v['GameTypeSettings'] = $updatedGameTypeSettings; 
          }
        }

        //only need to store the value entered
        foreach($gameTypeSetting as $settingName =>$settingArr){
          if($settingName == 'rules'){
            unset($gameTypeSetting[$settingName]);
          }
          else{
            foreach($settingArr as $settingKey => $settingVal){
              if($settingKey != 'enteredValue'){
                unset($settingArr[$settingKey]);
              }
              $gameTypeSetting[$settingName] = $settingArr['enteredValue'];
            }
          }
        }
      }

      //Only creates the game if met all criteria
      if(sizeof($errorArr) == 0 ){
        $passedSIteSettings = (($gameTypeSetting > 0) ? json_encode($gameTypeSetting) : NULL) ;
        //get the game class
        $typeIDClass = GetTypeIDClasse($conn, $game_typeID);
        //call the game type class function create tournament 
        $create =  $typeIDClass::CreateTournament($conn, $gameName, $game_typeID, $startDate, $endDate, $passedSIteSettings, $date, $selectedPlayers);
        exit(header("Location: index.php?created"));
      }

    break;

    //edit tournament
    case "editFutureGames":
      $previousGameTypeID = $_GET['gameType'];
      $editGameID = $_GET['editGameID'];
      $editGameType = $_GET['gameType'];
      $errorArr = [];
      $date = date("Y-m-d");
      $selectedPlayers = isset($_POST['checkedPlayer']) ? $_POST['checkedPlayer'] : null ;
      $gameName = $_POST['gameName'] === '' ? array_push($errorArr, "THE GAME HAS A NAME") : $_POST["gameName"];
      $startDate = isset($_POST['startDate']) === '' ?  array_push($errorArr, "THE START DATE ARE SET") : $_POST["startDate"];
      $endDate = isset($_POST["endDate"])  === '' ?  array_push($errorArr, "THE END DATE ARE SET") : $_POST["endDate"];
      $game_typeID = $_POST['selectGameType'];

      //validate the info posted by the user,passing $errorArr by reference
      $resultValidation = InfoValidation($conn,$game_typeID,$gameName,$endDate,$startDate,$selectedPlayers,$errorArr,$date);
      //get the settings for the game_type
      $gameTypeSettings = GetGameTypeSettings($conn,$game_typeID);
      $gameTypeSetting = json_decode($gameTypeSettings,true);

      //check if the gameTypeSettings is set
      if($gameTypeSetting > 0){
        //validate the gameTypeSetting values,passing $gameTypeSetting and $errorArr by reference
        $resultValidation = ValidateGameTypeSettings($conn,$gameTypeSetting,$errorArr);
        $updatedGameTypeSettings = json_encode($gameTypeSetting);
        //update the values entered into gameTypeSettings column
        foreach($game_typeArray as $k=>&$v){
          if($v['ID'] == $game_typeID){
            $v['GameTypeSettings'] = $updatedGameTypeSettings; 
          }
        }

        //only need to store the value entered
        foreach($gameTypeSetting as $settingName =>$settingArr){
          if($settingName == 'rules'){
            unset($gameTypeSetting[$settingName]);
          }
          else{
            foreach($settingArr as $settingKey => $settingVal){
              if($settingKey != "enteredValue"){
                unset($settingArr[$settingKey]);
                $gameTypeSetting[$settingName] = $settingArr;
              }
              else if($settingKey === "enteredValue"){
                unset($settingArr[$settingKey]);
                $gameTypeSetting[$settingName] = $settingVal;
              }
            }
          }
        }
      }

      //Only creates the game if met all criteria
      if(sizeof($errorArr)==0){
        $passedSIteSettings = (($gameTypeSetting > 0) ? json_encode($gameTypeSetting) : NULL) ;
        //get the game class
        $typeIDClass = GetTypeIDClasse($conn, $game_typeID);
        $previousIDClass = GetTypeIDClasse($conn,$previousGameTypeID);
        //call the game type class function edit tournament 
        $editFutureGame =  $typeIDClass::EditFutureGame($conn, $gameName, $game_typeID, $startDate, $endDate, $passedSIteSettings, $editGameID,$selectedPlayers,$previousIDClass, $typeIDClass);
      }

    break;

    default:
    // echo "Unknown Option!";
  }

  switch($_GET['processType'] ?? "unknown"){
    //future game deleted
    case "deleted":
      echo "<div class='deleteFutureGameGoodMessage'><p>FUTURE GAME DELETED SUCESSFULLY</p></div>";
    break;

    //end all vs all games
    case "Ended":
      echo "<div class='deleteGames'><p>GAME ENDED SUCESSFULLY</p></div>";
    break;

    //edit future game
    case "editFutureGame":
      $editGameID = $_GET['editGameID'];
      //get the game type site settings
      $siteSettingsJson = json_decode(GetSiteSettingGameType($conn,$editGameID),true);
      //get the game setting (entered value)
      $gameSiteSetting = json_decode(GetGameSiteSetting($conn,$editGameID),true);

      foreach($siteSettingsJson as $jsonKey){
        foreach($jsonKey as $settingName){
          foreach($gameSiteSetting as $gameSettingKey=>$gameSettingVal){
            //if the key exists, insert the enteredValue to the game type setting
            if($gameSettingKey == $settingName){
              $siteSettingsJson[$settingName]['enteredValue'] = $gameSettingVal;
            }
          }
        }
      }
      //make the array a json string
      $siteSettingsJson = json_encode($siteSettingsJson);
      $selectedGameType = $_GET['gameType'];
      $selectedP = json_decode($_GET['selectedP']);
      $gameArray = GetGameName($conn, $editGameID);
      $gameArr = json_decode($gameArray['siteSettings'],true);
    break;

    //Point-Based (all vs all)
    case "game":
      $gameID = $_GET['gameID'];
      $gameInfo = GetGameInfo($conn, $gameID);
      $classInfoName = GameTypeClasses($conn,$gameID);

      if(isset($_GET['Edited'])){
        echo "<div class='scoreGoodMessage'><p>SCORES EDITED SUCESSFULLY</p></div>";
      }

      if(isset($_GET['Updated'])){
        echo "<div class='scoreGoodMessage'><p>SCORES UPDATED SUCESSFULLY</p></div>";
      }

      if(isset($_GET['Inserted'])){
        echo "<div class='scoreGoodMessage'><p>SCORES INSERTED SUCESSFULLY</p></div>";
      }
    break;

    //add today's points (all vs all)
    case "todayGame":
      $gameID = $_GET['todayGameID'];
      $today = date("Y-m-d");
      $gameInfo = GetGameInfo($conn,$gameID);
      $pIDs =json_decode($gameInfo['SelectedPlayers']);
      $playerIDs = implode(",",$pIDs);
      $gameInfo = GetGameInfo($conn, $gameID);
      $classInfoName = GameTypeClasses($conn,$gameID);
    break;

    //edit points
    case "editGameDay":
      $gameID = $_GET['gameID'];
      $gameInfo = GetGameInfo($conn, $gameID);
      $classInfoName = GameTypeClasses($conn,$gameID);
    break;

    default:
    //echo "Unknown Option!";
  }

