<?php
  require_once 'dbConnnection.php';
  require_once 'fixtureFunction.php';
  // sleep(2);


  $connection = new DBConnection();
  $conn = $connection->getConnection();

  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

  //UPDATES THE WINNER IN KNOCKOUT GAMES
  if(isset($_REQUEST['updateKoWinner'])){
    // whats getting passed from ajax
    try{
      $matchID = $_REQUEST['updateKoWinner']['matchID'];
      $gameID = $_REQUEST['updateKoWinner']['gameID'];
      $winnerID = $_REQUEST['updateKoWinner']['winnerID'];

      $stmt = $conn->prepare("UPDATE `knockout_games` SET `Winner` = ? WHERE `GameID` = ? AND `MatchID` = ?");
      $stmt->bind_param("iii", $winnerID, $gameID,$matchID);
      $stmt->execute();
      $stmt->close();
    
      $array = array(
        "Winner" => "Updated",
        "ID" => $winnerID);
    } catch (Exception $e) {
      $array = array(
        "msg" => "error",
        "success" => 0);
    }
    echo json_encode($array);
  }

  //CREATES NEW ROUND KO GAMES
  if(isset($_REQUEST['createNewRound'])){
      // whats getting passed form ajax
    try{
        $gameID = $_REQUEST['createNewRound']['gameID'];
        $nextRoundID = $_REQUEST['createNewRound']['nextRoundID'];
        $winnersArray = $_REQUEST['createNewRound']['winnersArray'];
        
        //if the player array is odd then add 1 extra player 
        if(count($winnersArray) % 2 != 0 ) {
            array_push($winnersArray,"0");
        }
  
        //randomize the order of the elements in the array
        $shuffled_array = array();
        shuffle($winnersArray);
        $game = array();
        $game = array_chunk($winnersArray,2);

        foreach($game as $match){
          $pl1ID = $match[0];
          $pl2ID = $match[1];
        
          $stmt = $conn->prepare("INSERT INTO `knockout_games` (`GameID`, `Player1ID`,  `Player2ID`,  `Round` ) VALUES (?, ?, ? , ?)");
          $stmt->bind_param("iiii", $gameID, $pl1ID, $pl2ID, $nextRoundID);
          $stmt->execute();
          $stmt->close();
        }
        $playerMatch = [];
        $st = $conn->prepare("SELECT `ko`.`Player1ID`,`ko`.`Player2ID`,`ko`.`MatchID`,`ko`.`Round`,
                  CONCAT(COALESCE(p1.`Name`,'Extra'),' ', COALESCE(p1.`Lastname`,'Player')) AS P1Name,
                  CONCAT(COALESCE(p2.`Name`,'Extra'),' ', COALESCE(p2.`Lastname`,'Player')) AS P2Name
                  FROM game `g`
                  INNER JOIN `knockout_games` `ko` ON `ko`.`GameID` = `g`.`ID`
                  INNER JOIN `game_type` `gt` ON `gt`.`ID` = g.`TypeID`
                  LEFT JOIN game_players p1 ON ko.`Player1ID` = p1.`ID`
                  LEFT JOIN game_players p2 ON ko.`Player2ID` = p2.`ID`
                  WHERE `g`.`ID` =  ? AND `ko`.`Round`= ?
                  GROUP BY `ko`.`Round`, `ko`.`Player1ID`,`ko`.`Player2ID`;");
        $st->bind_param("ii", $gameID, $nextRoundID);
        $st->execute();
        $result = $st->get_result();
        while($row = $result->fetch_assoc()) {
          $playerMatch[] = $row;
        }
        $st->close();

        $array = array(
          "gameID" => $gameID,
          "nextRoundID" => $nextRoundID,
          "winnersArray" => $game,
          "playerMatch" => $playerMatch
        );
    }catch(Exception $e) {
      $array = array(
        "msg" => "error",
        "success" => 0);
    }
    echo json_encode($array);
  }

  //GET THE KO WINNER NAME
  if(isset($_REQUEST['koWINNER'])){
    // whats getting passed from ajax
    try{
      $gameID = $_REQUEST['koWINNER']['gameID'];
      $winnerID = $_REQUEST['koWINNER']['winnerID'];
      $round = $_REQUEST['koWINNER']['Round'];

      $stmt = $conn->prepare("INSERT INTO `competition_winners` (`GameID`, `WinnerID` ) VALUES (?, ?)");
      $stmt->bind_param("ii", $gameID, $winnerID);
      $stmt->execute();
      $stmt->close();

      $st = $conn->prepare("SELECT p.ID, p.`Name`,p.`Lastname`
                            FROM knockout_games k
                            INNER JOIN game_players p ON p.`ID` = k.`Winner`
                            WHERE GameID = ? AND `Round` = ? ;");
      $st->bind_param("ii", $gameID, $round);
      $st->execute();
      $st->store_result();
      $st->bind_result($wID,$wName, $wLastname);
      $st->fetch();
      $st->close();

      $win = array(
        "id"=>$wID,
        "name"=>$wName,
        "lastname"=>$wLastname
      );
    } catch (Exception $e) {
      $win = array(
        "msg" => "error",
        "success" => 0);
    }
    echo json_encode($win);
  }

  //CHECK IF SCORE EXIST TEMPORARY SCORES
  if(isset($_REQUEST['checkIfScoresExist'])){
    // whats getting passed from ajax
    $gameID = $_REQUEST['checkIfScoresExist']['gameID'];
    $date = $_REQUEST['checkIfScoresExist']['date'];

    try{
      $arr= [];
      $stmt = $conn->prepare("SELECT * FROM `game_score` WHERE `GameID` = ? AND `date` = ? ;");
      $stmt->bind_param("is", $gameID, $date);
      $stmt->execute();
      $result = $stmt->get_result();
      while($row = $result->fetch_assoc()) {
        $arr[] = $row;
      }
    }catch(Exception $e){
      $array = array(
        "msg" => "Error Selecting Game Temporary Score ",
        "success" => 0);
    }

    if(count($arr) > 0){
      $array = array(
        "msg" => "Temporary score exists",
        "success" => 1);
    }
    else{
      $array = array(
        "msg" => "Temporary score does not exists",
        "success" => 0);
    }
    echo json_encode($array);
  }

  //CHECK IF SCORE EXIST TEMPORARY SCORES
  if(isset($_REQUEST['ifScoreExist'])){
    // whats getting passed from ajax
    $gameID = $_REQUEST['ifScoreExist']['gameID'];
    $date = $_REQUEST['ifScoreExist']['date'];
    $playerID = $_REQUEST['ifScoreExist']['playerID'];

    try{
      $stmt = $conn->prepare("SELECT * FROM 
      (
      SELECT 'Live' AS `Table`, `GameID`, `Date`, `PlayerID`, DailyScore, ScoreMetadata FROM `game_score`
      UNION 
      SELECT 'Temporary' AS `Table`, `GameID`, `Date`, `PlayerID`, DailyScore, ScoreMetadata  FROM `game_temporary_score`
      ) X
      WHERE `gameID`= ?  AND `date`= ? AND `playerID`= ?;");
      $stmt->bind_param("isi", $gameID, $date, $playerID);
      $stmt->execute();
      $arr = $stmt->get_result()->fetch_assoc();
    }catch(Exception $e){
      $array = array(
        "msg" => "Error Selecting Game Temporary Score ",
        "success" => 0);
    }

    if($arr){
      $values = json_decode($arr['ScoreMetadata']);
        if($arr['Table'] === 'Live'){
          if($arr['DailyScore'] === null){
            $values = "Replacing non-played score";
            $array = array(
              "msg" => "You are changing Live Score for this day.
            ".$values."",
              "success" => 2);
          }
          else{
            $values = implode(",",$values);
            $array = array(
              "msg" => "You are changing Live Score for this day.
                    Previous Value: ".$values."",
              "success" => 2);
          }
        }
        else if($arr['Table'] === 'Temporary'){
            if($arr['DailyScore'] === null){
              $values = "Replacing non-played score";
              $array = array(
                "msg" => "You are changing Temporary Score for this day.
              ".$values."",
                "success" => 3);
            }
            else{
              $values = implode(",",$values);
              $array = array(
                "msg" => "You About to Change Existing Temporary Score That Have Not Been Put Live.
  Previous Value: ".$values."",
                  "success" => 3
      );
    }
    }
    else{
      if($arr['DailyScore'] === null){
        $values = "Replacing non-played score";
        $array = array(
          "msg" => "Temporary score exists",
          "msg" => "You are changing score for this day.
        ".$values."",
          "success" => 1);
      }
      else{
        $values = implode(",",$values);
        $array = array(
          "msg" => "Temporary score exists",
          "msg" => "You are changing score for this day.
                  Previous Value: ".$values."",
          "success" => 1);
      }
    }
    }
    else{
      $array = array(
        "msg" => "Temporary score does not exists",
        "success" => 0);
    }
    echo json_encode($array);
  }

  //CHECK IF SCORE EXIST TEMPORARY SCORES
  if(isset($_REQUEST['addPoints'])){
    // whats getting passed from ajax
    $gameID = $_REQUEST['addPoints']['gameID'];
    $date = $_REQUEST['addPoints']['date'];
    $dateEntered = $_REQUEST['addPoints']['dateEntered'];
    $playerID = $_REQUEST['addPoints']['playerID'];
    $totalAttempt = $_REQUEST['addPoints']['totalDailyScore'];
    if($totalAttempt == ""){
      $totalAttempt = null;
    }
    $attemptsValueArray = json_encode($_REQUEST['addPoints']['attemptsValuesArray']);

    try{
      $stmt = $conn->prepare("INSERT INTO `game_temporary_score` (`PlayerID`, `GameID`, `Date`, `DailyScore`, `ScoreMetadata`,`DateScoreEntered` ) VALUES (?, ?, ? , ?, ?, ?)");
      $stmt->bind_param("iisiss", $playerID, $gameID, $date, $totalAttempt, $attemptsValueArray, $dateEntered);
      $stmt->execute();
      $stmt->close();

      $array = array(
        "Status" => "Inserted",
        "playerID" => $playerID,
        "gameID" => $gameID,
        "dateEntered" => $dateEntered,
        "date" => $date,
        "dailyScore" => $totalAttempt,
        "attemptsValuesArray" => $attemptsValueArray
      );
    }catch (Exception $e) {
      $array = array(
        "msg" => "Error Inserting into game temporary score",
        "success" => 0);
    }
    echo json_encode($array);
  }

  //IF SCORE EXIST TEMPORARY SCORES UPDATE TABLE
  if(isset($_REQUEST['updateExistScore'])){
    // whats getting passed from ajax
    $gameID = $_REQUEST['updateExistScore']['gameID'];
    $date = $_REQUEST['updateExistScore']['date'];
    $dateEntered = $_REQUEST['updateExistScore']['dateEntered'];
    $playerID = $_REQUEST['updateExistScore']['playerID'];
    $totalAttempt = $_REQUEST['updateExistScore']['totalDailyScore'];
    if($totalAttempt == ""){
      $totalAttempt = null;
    }
    $attemptsValueArray = json_encode($_REQUEST['updateExistScore']['attemptsValuesArray']);
  
    try{
      $stmt = $conn->prepare("UPDATE `game_temporary_score` SET `DailyScore` = ?, `ScoreMetadata` = ? WHERE PlayerID = ? AND `Date`=?;");
      $stmt->bind_param("isis", $totalAttempt, $attemptsValueArray, $playerID, $date);
      $stmt->execute();
      $stmt->close();
    }catch(Exception $e){
      $array = array(
        "msg" => "ERROR Updating game_temporary_score",
        "success" => 0);
    }
  
      $array = array(
        "Status" => "upda",
        "playerID" => $playerID,
        "gameID" => $gameID,
        "dateEntered" => $dateEntered,
        "date" => $date,
        "dailyScore" => $totalAttempt,
        "attemptsValuesArray" => $attemptsValueArray,
        "msg" => "You are changing previous score for this day.
                  Previous Value: ". $arr['ScoreMetadata']."",
        "success" => 1
      );

    echo json_encode($array);  
  }

  //UPDATE TEMPORARY SCORES
  if(isset($_REQUEST['updatePoints'])){
    // whats getting passed from ajax
    try{
      $gameID = $_REQUEST['updatePoints']['gameID'];
      $playerID = $_REQUEST['updatePoints']['playerID'];
      $date = $_REQUEST['updatePoints']['date'];
      $dateEntered = date("Y-m-d");
      $dailyScore = $_REQUEST['updatePoints']['totalDailyScore'];
      if($dailyScore == ""){
        $dailyScore = null;
      }
      $scoreMetadata = json_encode($_REQUEST['updatePoints']['attemptsValuesArray']);

      $stmt = $conn->prepare("UPDATE `game_temporary_score` SET `DailyScore` = ?, `ScoreMetadata` = ?, `Date` = ?, `DateScoreEntered`= ? WHERE `PlayerID` = ? AND `GameID`= ?");
      $stmt->bind_param("isssii", $dailyScore,  $scoreMetadata,$date,$dateEntered, $playerID, $gameID);
      $stmt->execute();
      $stmt->close();
    
      $array = array(
        "Status" => "Inserted",
        "playerID" => $playerID,
        "gameID" => $gameID,
        "dailyScore" => $dailyScore,
        "attemptsValuesArray" => $scoreMetadata
      );
    } catch (Exception $e) { 
        $array = array(
          "msg" => "Error Updating game temporary score",
          "success" => 0);
    }
    echo json_encode($array);
  }

  //ADD ALL THE SCORE TO THE SCORE TABLE
  if(isset($_REQUEST['addPointsDay'])){
    // whats getting passed from ajax
    try{
      $gameID = $_REQUEST['addPointsDay']['gameID'];
      $playerID = $_REQUEST['addPointsDay']['playerID'];
      $DailyScore = $_REQUEST['addPointsDay']['DailyScore'];
      if($DailyScore == ''){
        $DailyScore = null;
      }
      $scoreMetadata = $_REQUEST['addPointsDay']['attemptsValuesArray'];
      $gameDate = $_REQUEST['addPointsDay']['gameDate'];

      $stmt = $conn->prepare("INSERT INTO `game_score` (`playerID`,`gameID`,`date`,`dailyScore`,`scoreMetadata`) VALUES (?,?,?,?,?)");
      $stmt->bind_param("iisis",$playerID,$gameID,$gameDate, $DailyScore, $scoreMetadata);
      $stmt->execute();
      $stmt->close();
    
      $array = array(
        "Status" => "Inserted",
        "playerID" => $playerID,
        "gameID" => $gameID,
        "gameDate" => $gameDate,
        "DailyScore" => $DailyScore,
        "attemptsValuesArray" => $scoreMetadata,
      );
    } catch (Exception $e) {
        $array = array(
          "msg" => "error",
          "success" => 0);
    }
    echo json_encode($array);
  }

  //UPDATE THE DAILY SCORE
  if(isset($_REQUEST['updatePointsDay'])){
    // whats getting passed from ajax
    try{
      $gameID = $_REQUEST['updatePointsDay']['gameID'];
      $playerID = $_REQUEST['updatePointsDay']['playerID'];
      $dailyScore = $_REQUEST['updatePointsDay']['DailyScore'];
      if($dailyScore ==''){
        $dailyScore = null;
      }
      $scoreMetadata = $_REQUEST['updatePointsDay']['attemptsValuesArray'];
      if($scoreMetadata ==''){
        $scoreMetadata = null;
      }
      $gameDate = $_REQUEST['updatePointsDay']['gameDate'];

      $stmt = $conn->prepare("UPDATE `game_score` SET `dailyScore` = ?, `scoreMetadata` = ? WHERE `playerID` = ? AND `gameID`= ? AND `date`= ?");
      $stmt->bind_param("isiis", $dailyScore,  $scoreMetadata, $playerID, $gameID, $gameDate);
      $stmt->execute();
      $stmt->close();
    
      $array = array(
        "Status" => "Inserted",
        "playerID" => $playerID,
        "gameID" => $gameID,
        "dailyScore" => $dailyScore,
        "attemptsValuesArray" => $scoreMetadata,
        "gameDate" => $gameDate
      );
    }catch (Exception $e) {
        $array = array(
          "msg" => "error",
          "success" => 0);
    }
    echo json_encode($array);
  }

  //GET THE KO ROUND DRAW
  if(isset($_REQUEST['getRounds'])){
    //whats getting passed from ajax
    try{
      $roundID = $_REQUEST['getRounds']['round'];
      $tournamentID = $_REQUEST['getRounds']['tournamentID'];
      $round = [];
      $getRounds = $conn->prepare("SELECT `lg`.`Player1ID`,`lg`.`Player2ID`,
            `lg`.`MatchID`,`lg`.`Round`,
            CONCAT(COALESCE(p1.`Name`,'FREE'),' ', COALESCE(p1.`Lastname`,'THIS ROUND')) AS P1Name,
            CONCAT(COALESCE(p2.`Name`,'FREE'),' ', COALESCE(p2.`Lastname`,'THIS ROUND')) AS P2Name,`lg`.`Winner`,lg.`ResultP1`,lg.`ResultP2`
            FROM game `g`
            INNER JOIN `tournament_games` `lg` ON `lg`.`GameID` = `g`.`ID`
            INNER JOIN `game_type` `gt` ON `gt`.`ID` = g.`TypeID`
            LEFT JOIN game_players p1 ON lg.`Player1ID` = p1.`ID` AND lg.`Player1ID` > 0
            LEFT JOIN game_players p2 ON lg.`Player2ID` = p2.`ID` AND lg.`Player2ID` > 0
            WHERE `g`.`ID` =  ? AND lg.`Round` = ?
            GROUP BY `lg`.`Round`, `lg`.`Player1ID`,`lg`.`Player2ID`;");
      $getRounds->bind_param("ii", $tournamentID, $roundID);
      $getRounds->execute();
      $result = $getRounds->get_result();
      while($row = $result->fetch_assoc()){
        $round[] = $row;
      }
      $getRounds->close();
      $array =  $round;
    } catch (Exception $e) {
        $array = array(
          "msg" => "error",
          "success" => 0);
    }
    echo json_encode($array);
  }

  //UPDATE TOURNAMENT ROUND WINNER AND INSERT SCORES INTO TOURNAMENT SCORES
  if(isset($_REQUEST['SubmitResults'])){
    //whats getting passed from ajax
    try{
      $gameID = $_REQUEST['SubmitResults']['gameID'];
      $homeScore = $_REQUEST['SubmitResults']['homeScore'];
      $awayScore =  $_REQUEST['SubmitResults']['awayScore'];
      $player1ID =  $_REQUEST['SubmitResults']['player1ID'];
      $player2ID =  $_REQUEST['SubmitResults']['player2ID'];
      $player1Points =  $_REQUEST['SubmitResults']['player1Points'];
      $player2Points =  $_REQUEST['SubmitResults']['player2Points'];
      $matchID = $_REQUEST['SubmitResults']['matchID'];
      $round = $_REQUEST['SubmitResults']['round'];
      $winnerID = $_REQUEST['SubmitResults']['winnerID'];
      $date = date("Y-m-d");

      //update winner in tournament games
      $updateResults = $conn->prepare("UPDATE `tournament_games` SET `Winner` = ?, `ResultP1` = ?,`ResultP2` = ? , `GameID` = ? WHERE  `MatchID` = ?;");
      $updateResults->bind_param("iiiii", $winnerID, $homeScore, $awayScore, $gameID, $matchID);
      $updateResults->execute();
      $updateResults->close();

      //insert player1 score into tournament_score table
      $stmt = $conn->prepare("INSERT INTO `tournament_score` (`GameID`, `PlayerID`, `DailyScore`, `Round`, `Points`, `Date`, `MatchID`) VALUES (?, ?, ?, ? , ?, ?, ?)");
      $stmt->bind_param("iiiiisi",  $gameID, $player1ID, $homeScore , $round , $player1Points, $date, $matchID);
      $stmt->execute();
      $stmt->close();

      //insert player2 score into tournament_score table
      $stmt2 = $conn->prepare("INSERT INTO `tournament_score` ( `GameID`, `PlayerID`, `DailyScore`, `Round`, `Points`, `Date`, `MatchID`) VALUES (?, ?, ?, ? , ?, ?, ?)");
      $stmt2->bind_param("iiiiisi",  $gameID, $player2ID, $awayScore , $round , $player2Points, $date, $matchID);
      $stmt2->execute();
      $stmt2->close();

      $array =  array(
        "Status" => "updated",
        "matchID" => $matchID,
        "homeScore" => $homeScore,
        "awayScore" => $awayScore,
        "winner" => $winnerID
      );
    } catch (Exception $e) {
        $array = array(
          "msg" => "error",
          "success" => 0);
    }
    echo json_encode($array);
  }

  //UPDATE WINNER AND UPDATE SCORE
  if(isset($_REQUEST['updateResults'])){
    //whats getting passed from ajax
    try{
      $gameID = $_REQUEST['updateResults']['gameID'];
      $homeScore = $_REQUEST['updateResults']['homeScore'];
      $awayScore =  $_REQUEST['updateResults']['awayScore'];
      $player1ID =  $_REQUEST['updateResults']['player1ID'];
      $player2ID =  $_REQUEST['updateResults']['player2ID'];
      $player1Points =  $_REQUEST['updateResults']['player1Points'];
      $player2Points =  $_REQUEST['updateResults']['player2Points'];
      $matchID = $_REQUEST['updateResults']['matchID'];
      $round = $_REQUEST['updateResults']['round'];
      $winnerID = $_REQUEST['updateResults']['winnerID'];
      $date = date("Y-m-d");

      //update tournament winner
      $updateResults = $conn->prepare("UPDATE `tournament_games` SET `Winner` = ?, `ResultP1` = ?,`ResultP2` = ? WHERE  `MatchID` = ?;");
      $updateResults->bind_param("iiii", $winnerID, $homeScore, $awayScore, $matchID);
      $updateResults->execute();
      $updateResults->close();

      $stmt = $conn->prepare(" UPDATE `tournament_score` SET `DailyScore` = ?, `Points` = ? Where `MatchID` = ? AND `Round` = ? AND `PlayerID` = ?;");
      $stmt->bind_param("iiiii", $homeScore , $player1Points, $matchID, $round, $player1ID);
      $stmt->execute();
      $stmt->close();

      $stmt2 = $conn->prepare(" UPDATE `tournament_score` SET `DailyScore` = ?, `Points` = ? Where `MatchID` = ? AND `Round` = ? AND `PlayerID` = ?;");
      $stmt2->bind_param("iiiii", $awayScore , $player2Points, $matchID, $round, $player2ID);
      $stmt2->execute();
      $stmt2->close();

      $array =  array(
        "Status" => "updated",
        "matchID" => $matchID,
        "homeScore" => $homeScore,
        "awayScore" => $awayScore,
        "winner" => $winnerID
      );
    }catch (Exception $e) {
      $array = array(
        "msg" => "error",
        "success" => 0);
    }
   echo json_encode($array);
  };
  
  //DELETE FUTURE GAME
  if(isset($_REQUEST['toDelete'])){
    //whats getting passed from ajax
    try{
      $gameID = $_REQUEST['toDelete']['gameID'];
      $delete = $conn->prepare("DELETE FROM `game` WHERE `ID` = ?");
      $delete->bind_param("i", $gameID);
      $delete->execute();
      $delete->close();

      $array =  array(
        "Status" => "future game deleted");
    }catch (Exception $e) {
      $array = array(
        "msg" => "Error Deleting Future Games",
        "success" => 0);
    }
   echo json_encode($array);
  };

  //END ALL VS ALL GAMES
  if(isset($_REQUEST['endAllVsAllGames'])){
    $gameID = $_REQUEST['endAllVsAllGames']['gameId'];
    $topScorer = $_REQUEST['endAllVsAllGames']['topScorer'];

    try{
      try{
        $stmt = $conn->prepare("UPDATE `game` SET `Active` = '0' WHERE `ID` = ? ");
        $stmt->bind_param("i", $gameID);
        $stmt->execute();
        $stmt->close();
      }catch(Exception $e){return false;}

      try{
        $stmt = $conn->prepare("INSERT INTO `competition_winners` (`GameID`, `WinnerID`) VALUES (?, ?)");
        $stmt->bind_param("ii", $gameID, $topScorer);
        $stmt->execute();
        $stmt->close();
      }catch(Exception $e){return false;}

      $array =  array(
        "Status" => "ended",
        "gameID" => $gameID);

    }catch(Exception $e){
      $array = array(
        "ERRORmessage" => "ERROR updating",
        "gameID" => $gameID,
        "msg"=>0);
    }
    echo json_encode($array);
  }

  //END TOURNAMENT GAMES
  if(isset($_REQUEST['endTournamentGames'])){
    $tGameID = $_REQUEST['endTournamentGames']['tGameId'];
    $tTopScorer = $_REQUEST['endTournamentGames']['tTopScorer'];

    try{
      try{
        $stmt = $conn->prepare("UPDATE `game` SET `Active` = '0' WHERE `ID` = ? ");
        $stmt->bind_param("i", $tGameID);
        $stmt->execute();
        $stmt->close();
      }catch(Exception $e){return false;}
      
      try{
        $stmt = $conn->prepare("INSERT INTO `competition_winners` (`GameID`, `WinnerID`) VALUES (?, ?)");
        $stmt->bind_param("ii", $tGameID, $tTopScorer);
        $stmt->execute();
        $stmt->close();
      }catch(Exception $e){return false;}

      $array1 =  array(
        "Status" => "ended",
        "gameID" => $tGameID);

    }catch(Exception $e){
      $array1 = array(
        "msg" => "ERROR updating",
        "gameID" => $tGameID,
        "success"=> 0);
    }
    echo json_encode($array1);
  }

  //END KNOCKOUT GAMES
  if(isset($_REQUEST['endKoGames'])){
    $koGameId = $_REQUEST['endKoGames']['koGameId'];
    $koWinner = $_REQUEST['endKoGames']['koWinner'];

    try{
      $stmt = $conn->prepare("UPDATE `game` SET `Active` = '0' WHERE `ID` = ?");
      $stmt->bind_param("i", $koGameId);
      $stmt->execute();
      $stmt->close();

      $array =  array(
      "Status" => "ended",
      "gameID" => $koGameId);
    }catch(Exception $e){
      $array = array(
        "msg" => "ERROR updating",
        "gameID" => $koGameId,
        "success" => 0);
    }
    echo json_encode($array);
  }

  //UPDATE ALL THE SCORES WHEN EDITING DAY SCORE
  if(isset($_REQUEST['updateAllEditScore'])){
    $attempts = $_REQUEST['updateAllEditScore']['attempts'];
    $gameId = $_REQUEST['updateAllEditScore']['gameID'];
   
    try{
      $date = $_REQUEST['updateAllEditScore']['date'];
      foreach($attempts as $k=>$v){
        if($v === ""){
          unset($attempts[$k]);
        }
      }
    
      foreach($attempts as $k =>$v){
        $scoreMetadata = [];
        $player = $k;
        foreach($v as $value){
          if($value === ""){
            $value = json_encode(null);
            array_push($scoreMetadata,$value);
          }
          else{
            array_push($scoreMetadata,$value);
          }
        }

        $arrayVal = [];
        foreach($scoreMetadata as $score){
          if($score!="null"){
           array_push($arrayVal,$score);
          }
        }

        if(count($arrayVal)>0){
          $dailyScore = array_sum($arrayVal);
        }else{
          $dailyScore = null;
        }
        $scoreMetadata = json_encode($scoreMetadata);

        $stmt = $conn->prepare("UPDATE `game_score` SET `dailyScore` = ?, `scoreMetadata` = ? WHERE `gameID` = ? AND `date` = ? AND `playerID`=?");
        $stmt->bind_param("isisi", $dailyScore, $scoreMetadata, $gameId, $date, $player);
        $stmt->execute();
        $stmt->close();

        $array =  array(
          "Status" => "updated",
          "gameID" => $gameId);
      }
    }catch(Exception $e){
      $array = array(
        "ERRORmessage" => "ERROR UPDATING DAILY SCORE",
        "gameID" => $gameId,
        "success" => 0);
    }
    echo json_encode($array);
  }

  //INSERT AT ONCE ALL THE DAY SCORE
  if(isset($_REQUEST['insertAllDayScore'])){
      $attempts = $_REQUEST['insertAllDayScore']['attempts'];
      $gameId = $_REQUEST['insertAllDayScore']['gameID'];
      $date = $_REQUEST['insertAllDayScore']['date'];
      $arrayVal = [];
    
      foreach($attempts as $k=>$v){
        if($v === ""){
          unset($attempts[$k]);
        }
      }

     try{
      foreach($attempts as $k =>$v){
        $scoreMetadata = [];
        $player = $k;
        foreach($v as $value){
          if($value ==""){
            $value = null;
            array_push($scoreMetadata,$value);
          }
          else{
            array_push($scoreMetadata,$value);
          }
        }

        foreach($scoreMetadata as $score){
          if($score!=null){
            $dailyScore = array_sum($scoreMetadata);
          }else{
            $dailyScore = null;
          }
        }

        $scoreMetadata = json_encode($scoreMetadata);

        $stmt = $conn->prepare("INSERT INTO `game_score` (`playerID`,`gameID`,`date`,`dailyScore`,`scoreMetadata`) VALUES (?, ?, ?, ?, ?);");
        $stmt->bind_param("iisis", $player, $gameId, $date, $dailyScore, $scoreMetadata);
        $stmt->execute();
        $stmt->close();

        $stmt1 = $conn->prepare("DELETE FROM `game_temporary_score` WHERE `gameID` = ? AND `date`=?");
        $stmt1->bind_param("is", $gameId,$date);
        $stmt1->execute();
        $stmt1->close();

        $array =  array(
          "Status" => "updated",
          "gameID" => $gameId,
        );
      }
     }catch(Exception $e){
        $array = array(
          "msg" => "Failed to insert into game_score table",
          "success" => 0);
     }
    echo json_encode($array);
  }

  //INSERT AT ONCE ALL THE DAY SCORE IF EXISTS
  if(isset($_REQUEST['updateAllExistDayScore'])){ 
    $attempts = $_REQUEST['updateAllExistDayScore']['attempts'];
    $gameId = $_REQUEST['updateAllExistDayScore']['gameID'];
    $date = $_REQUEST['updateAllExistDayScore']['date'];
    $arrayVal = [];
  
    foreach($attempts as $k=>$v){
      if($v === ""){
        unset($attempts[$k]);
      }
    }
    
    try{
      foreach($attempts as $k =>$v){
        $scoreMetadata = [];
        $player = $k;
        foreach($v as $value){
          if($value ==""){
            $value = null;
            array_push($scoreMetadata,$value);
          }else{
            array_push($scoreMetadata,$value);
          }
        }

        foreach($scoreMetadata as $score){
          if($score!=null){
            $dailyScore = array_sum($scoreMetadata);
          }else{
            $dailyScore = null;
          }
        }

        $scoreMetadata = json_encode($scoreMetadata);

        $stmt = $conn->prepare("UPDATE `game_score` SET `dailyScore` = ?,`scoreMetadata`=?  WHERE `gameID` = ? AND `date`=? AND `playerID`=?;");
        $stmt->bind_param("isisi", $dailyScore, $scoreMetadata, $gameId, $date, $player);
        $stmt->execute();
        $stmt->close();

        $stmt1 = $conn->prepare("DELETE FROM `game_temporary_score` WHERE `gameID` = ? AND `date`=?");
        $stmt1->bind_param("is", $gameId,$date);
        $stmt1->execute();
        $stmt1->close();

        $array =  array(
          "Status" => "updated",
          "gameID" => $gameId,
        );
      }
    }catch(Exception $e){
        $array = array(
          "msg" => "Failed to insert into game_score table",
          "success" => 0);
    }
    echo json_encode($array);
  }


  //INSERT ALL TEMPORARY SCORE ENTERED BUT NOT PUT LIVE
  if(isset($_REQUEST['tempScoresInfo'])){

    $gameId = $_REQUEST['tempScoresInfo']['gameID'];

    try{
      $stmt = $conn->prepare("INSERT INTO `game_score` (`PlayerID`, `GameID`, `Date`, `DailyScore`, `ScoreMetadata`)
       SELECT `PlayerID`, `GameID`, `Date`, `DailyScore`, `ScoreMetadata` FROM `game_temporary_score`
       WHERE GameID = ?;");
      $stmt->bind_param("i", $gameId);
      $stmt->execute();
      $stmt->close();

      $stmt1 = $conn->prepare("DELETE FROM `game_temporary_score` WHERE `gameID` = ?");
      $stmt1->bind_param("i", $gameId);
      $stmt1->execute();
      $stmt1->close();

      $array =  array(
        "Status" => "inserted",
        "gameID" => $gameId,
      );

    }catch(Exception $e){
      $array = array(
        "msg" => "Failed to insert into game_score table",
        "success" => 0);
    }
  }
