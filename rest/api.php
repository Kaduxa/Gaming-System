<?php

  chdir ("..");
  header("Content-Type:application/json");

  switch($_GET['Request']){
    case 'GetActiveGames':
      $activeGames = getActiveGames();
    break;

    case 'GetGameInfo':
      include 'includes/dbConnnection.php';
      $connection = new DBConnection();

      $gameID = isset($_GET['GameID']) ? $_GET['GameID'] : '';
      $gameTypeID = getGameTypeID($gameID);
      $json = array();
      
      if(is_numeric($gameID) === false){
        array_push($json['Result']="Error");
        array_push($json['Reason']="Make sure the Game ID is a valid number");
        echo json_encode($json);
        return;
      }
      else if( $gameID <= 0 ){
        array_push($json['Result']="Error");
        array_push($json['Reason']= "Make sure the Game ID is positive");
        echo json_encode($json);
        return;
      }
      else if( floor( $gameID ) != $gameID){
        array_push($json['Result']="Error");
        array_push($json['Reason']= "Make sure the Game ID is an integer");
        echo json_encode($json);
        return;
      }
      else if($gameTypeID === null){
        array_push($json['Result']="Error");
        array_push($json['Reason']= "Game ID Doesn't Exist");
        echo json_encode($json);
        return;
      }
      else{
        if($gameTypeID == 3){
          $getKoInfo = getKoRoundsInfo($gameID);
        }
        else{
          $getGamePoints = getGamePoints($gameID);
        }
      }
    break;
  }

  /////////////////////////////////////////////////////////////////////////
  //////////////////////   Functions    ///////////////////////////////////
  /////////////////////////////////////////////////////////////////////////

  //////////////////////  Responses  //////////////////////////////////////

  function activeGames( $gameID, $gameName, $startDate, $endDate, $gameTypeID, $gameType){
    $response['GameID'] = $gameID;
    $response['Name'] = $gameName;
    $response['StartDate'] = $startDate;
    $response['EndDate'] = $endDate;
    $response['GameTypeID'] = $gameTypeID;
    $response['NameType'] = $gameType;
    return $response;
  }

  function gamePoints( $playerName, $playerLastname, $score){
    $response['Name'] = $playerName;
    $response['Lastname'] = $playerLastname;
    $response['DailyScore'] = $score;
    return $response;
  }

  function koInfo($p1ID,  $p1Name, $p2ID, $p2Name,$round, $roundWinner){
    $response['player1ID'] = $p1ID;
    $response['player1Name'] = $p1Name;
    $response['player2ID'] = $p2ID;
    $response['player2Name'] = $p2Name;
    $response['round'] = $round;
    $response['roundWinner'] = $roundWinner;
    return $response;
  }

  /////////////////////   Queries   //////////////////////////////////////

  function getActiveGames(){
    include 'includes/dbConnnection.php';
    $connection = new DBConnection();
    $active= [];
    $activeGames = mysqli_query(
          $conn = $connection->getConnection(),
          "SELECT g.`ID` as `gameID`,g.`Name`,g.`StartDate`,g.`EndDate`,gt.`NameType`,`gt`.`ID` as `gameTypeID` FROM game g
          INNER JOIN game_type gt ON g.`TypeID` = gt.`ID`
          WHERE g.`Active` = 1 ");

          while( $row = $activeGames->fetch_assoc() ){

              if($row['gameID'] !=NULL){
                  $var = activeGames( $row['gameID'], $row['Name'], $row['StartDate'], $row['EndDate'], $row['gameTypeID'], $row['NameType']);
                  array_push($active,$var);
              }else{
              gamePoints(NULL, NULL,"No Record Found");
              }
          }
          echo json_encode($active, JSON_PRETTY_PRINT);
          return $active;
  }
  
  function getGameTypeID($gameID){
    $connection = new DBConnection();
    //get the game type
    try{
      $getGameType = mysqli_query(
        $conn = $connection->getConnection(),
        "SELECT `TypeID` FROM `game` WHERE ID = $gameID;");
        while($row = $getGameType->fetch_assoc()){
          $gameTypeID = $row['TypeID'];
        }
        return $gameTypeID;
    }catch(Exception $e){
      $gameTypeID = null;
      return $gameTypeID;
    }
  }

  function getKoRoundsInfo($gameID){
    $connection = new DBConnection();
    $playersMatchArray = [];
    $getKoInfo = mysqli_query(
        $conn = $connection->getConnection(),
        "SELECT `g`.`Name` AS `Game Name`,`g`.`StartDate`,`g`.`EndDate`,`ko`.`Player1ID`,`ko`.`Player2ID`,
        `ko`.`MatchID`,`ko`.`Round`,
        CONCAT(COALESCE(p1.`Name`,'Extra'),' ', COALESCE(p1.`Lastname`,'Player')) AS P1Name,
        CONCAT(COALESCE(p2.`Name`,'Extra'),' ', COALESCE(p2.`Lastname`,'Player')) AS P2Name,`ko`.`Winner`
        FROM game `g`
        INNER JOIN `knockout_games` AS `ko` ON `ko`.`GameID` = g.`ID`
        LEFT JOIN `game_players` AS `p1` ON `ko`.`Player1ID` = p1.`ID`
        LEFT JOIN `game_players` AS `p2` ON ko.`Player2ID` = p2.`ID`
        WHERE `g`.`ID` =  $gameID
        GROUP BY `ko`.`Round`, `ko`.`Player1ID`,`ko`.`Player2ID`,`ko`.`Winner`;");

        while($row = $getKoInfo->fetch_assoc() ){
          $koGames = [];
          $variable = koInfo($row['Player1ID'], $row['P1Name'], $row['Player2ID'], $row['P2Name'],$row['Round'], $row['Winner']);
          array_push($playersMatchArray,$variable);
          $rounds['matches'] = $playersMatchArray;
          $koGames[] = $rounds;
        }
        if(!$getKoInfo){
          koInfo(NULL, NULL, 200,"No Record Found");
        }
        echo json_encode($koGames, JSON_PRETTY_PRINT);
        return $getKoInfo;
  }

  function getGamePoints($gameID){
    $connection = new DBConnection();
    $playerArray = [];
    $getGamePoints = mysqli_query(
    $conn = $connection->getConnection(),
        "SELECT `p`.`ID`,`p`.`Name`,`p`.`Lastname`,COALESCE(SUM(`s`.`dailyScore`),0) AS `Sum`
        FROM `game_players` `p`
        LEFT JOIN `game_score` AS `s` ON `s`.`playerID` = `p`.`ID`  AND `s`.`gameID` = $gameID
        WHERE p.`ID`> 0 AND `p`.`Status` = 1
        GROUP BY p.`ID` 
        ORDER BY `Sum` DESC,`Name`,`Lastname`;");
    while($row = $getGamePoints->fetch_assoc()){
      if($row['Sum'] != NULL){
        $variable =  gamePoints($row['Name'], $row['Lastname'], $row['Sum']);
        array_push($playerArray,$variable);
      }
      else{
        gamePoints(NULL, NULL,"No Record Found");
      }
    }
    echo json_encode($playerArray, JSON_PRETTY_PRINT);
    return $getGamePoints;
  }