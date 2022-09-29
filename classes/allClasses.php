<?php

  class GameInfo{
    private $gameID;
    private $gameName;
    private $gameTypeID;
    private $startDate;
    private $endDate;
    private $siteSettings;
    private $selectedPlayers;
    private $nameType;
    private $active;

    //when create games button is pressed
    static function  CreateTournament($conn, $name, $game_typeID, $startDate, $endDate, $siteSettings, $date, $selectedPlayers){
      //validate the date passed on request
      list($y, $m, $d) = explode('-',$startDate);
      $today = date("Y-m-d");
      
      //check if date passed is a Gregorian date
      if(checkdate($m, $d, $y)){
        //Games can be created (at least one month in the past) no more then that
        if($startDate >= date('Y-m-d', strtotime('-30 days'))){
          try{
            $selectedPlayers = json_encode($selectedPlayers);
            //create game
            $stmt = $conn->prepare("INSERT INTO `game` ( `Name`, `TypeID`, `StartDate`, `EndDate`, `SiteSettings` ,`SelectedPlayers`) VALUES ( ?, ? , ?, ?, ?, ?)");
            $stmt->bind_param("sissss",$name, $game_typeID, $startDate, $endDate, $siteSettings, $selectedPlayers);
            $stmt->execute();
            $newGameID = $stmt->insert_id;
            $stmt->close();
            return  $newGameID;
          }catch(Exception $e){
            $newGameID = null;
          }
        }
        else{
          echo "<div class='badMessage'><b>MAKE SURE THE START DATE IS NO MORE THAN 1 MONTH BEHIND</b></div><br>";  
        }
      }
      else{
        echo "<div class='badMessage'><b>MAKE SURE THE DATE IS IN VALID FORMAT</b></div><br>";
      }
    }

    //when edit future games button is pressed
    static function EditFutureGame($conn, $name, $game_typeID, $startDate, $endDate, $passedSIteSettings, $editGameID,$selectedPlayers,$previousIDClass, $typeIDClass){
      $today = date("Y-m-d");
      $resultCleanUp = $previousIDClass::CleanUpTable($conn,$editGameID);

      if($resultCleanUp === null || $resultCleanUp === true){
        $typeIDClass::SetupGameType($conn,$editGameID,$selectedPlayers);
        //validate the date passed on request
        list($y, $m, $d) = explode('-',$startDate);
        /*
          Only creates the game if the date selected is equal 
          Or bigger then the day the tournament is created
        */
        //check if date passed is a Gregorian date
        if(checkdate($m, $d, $y)){
          //Games can be created (at least one month in the past) no more then that
          if( $startDate >= date('Y-m-d', strtotime('-30 days'))){
            try{
              $selectedPlayers = json_encode($selectedPlayers);
              //update game's table
              $stmt = $conn->prepare("UPDATE `game` SET `Name` = ? , `TypeID` = ?, `StartDate` = ? , `EndDate` = ?, `siteSettings`= ?, `SelectedPlayers` = ?
              WHERE `ID` = ?");
              $stmt->bind_param("sissssi",$name, $game_typeID, $startDate, $endDate, $passedSIteSettings ,$selectedPlayers, $editGameID);
              $stmt->execute();
              $stmt->close();

              exit(header("Location: index.php?edited"));
            }catch(Exception $e){
              return false;
            }
          }
          else{
            echo "<div class='badMessageDate'><h4>MAKE SURE THE START DATE IS NO<BR> MORE THAN 1 MONTH BEHIND</h4></div>";
          }
        }
        else{
          echo "<div class='badMessageDate'><h4>MAKE SURE THE DATE IS IN VALID DATE</h4> </div>";
        }
      }
      else{
          echo "<div class='badMessageDate'><h4>ERROR DELETING PREVIOUS GAME TYPE - ".$previousIDClass."</h4></div>";
      }
    }

    function __construct($conn,$gameID){
      //get game info
      try{
          $stm = $conn->prepare("SELECT `g`.`ID`,g.`Name`,g.`TypeID`, g.`StartDate`,g.`EndDate`,g.`Active`,g.`siteSettings`,g.`SelectedPlayers`,gt.`NameType`
          FROM `game` g INNER JOIN game_type gt ON `g`.`TypeID`= `gt`.`ID` WHERE `g`.`ID` = ?;");
          $stm->bind_param("i", $gameID);
          $stm->execute();
          $stm->bind_result($this->gameID,$this->gameName,$this->gameTypeID,$this->startDate,$this->endDate,$this->active,$this->siteSettings,$this->selectedPlayers,$this->nameType); 
          $stm->fetch();
          $stm->close();
      }catch(Exception $e){
          throw new Exception("failed");  
      }
      return true;
    }

    //game information table
    public function GetInfoBox(){
      echo "<div class='ViewGameInfo' id='".$this->gameID."' data-type='".$this->gameTypeID."'>
              <table>
                  <tr class='gameinfosHead'>
                      <th>GAME NAME</th>
                      <th>GAME TYPE</th>
                      <th>START DATE</th>
                      <th>END DATE</th>
                  </tr>
                  <tr class='gameInfos' id='".$this->gameID."'> 
                      <td>".$this->gameName."</td>
                      <td>".$this->nameType."</td>
                      <td class='startDate'>".$this->startDate."</td>
                      <td class='endDate'>".$this->endDate."</td>
                  </tr>
              </table>
            </div><br>";
    }
  }

  class Knockout extends GameInfo{
    private $winner;
    private $knockoutGames;
    private $rounds;

    static function GetCompetitionLeader($conn,$gameID){
      //get knockout last winner
      try{
        $leader= [];
        $stm2 = $conn->prepare("SELECT * FROM `knockout_games` WHERE `gameid`= ?;");
        $stm2->bind_param("i", $gameID);
        $stm2->execute();
        $result = $stm2->get_result();
        while($row = $result->fetch_assoc()) {
          $leader[] = $row;
        }
        //gets the last person selected as winner
        $leader = end($leader);
        return $leader['Winner'];
        $stm2->close();
      }catch(Exception $e){
        $leader = null;
        return $leader;
      }
    }

    static function CreateTournament($conn, $name, $game_typeID, $startDate, $endDate, $passedSIteSettings, $date, $selectedPlayers){
      $newGameID = parent::CreateTournament($conn, $name, $game_typeID, $startDate, $endDate, $passedSIteSettings, $date, $selectedPlayers);
      static::SetupGameType($conn, $newGameID,$selectedPlayers);
    }

    static function SetupGameType($conn,$gameID,$selectedPlayers){
      $playersID = $selectedPlayers;
      $gameID = $gameID;
      $numberPlayers = count($playersID);

      //checks if the number of players are even, if not add extra player
      if($numberPlayers % 2 != 0){
        array_push($playersID, '0');
      }
    
      //then shuffle the order
      $shuffled_array = array();
      $keys = array_keys($playersID);
      shuffle($keys);

      foreach ($keys as $key){
        $shuffled_array[$key] = $playersID[$key];
      }

      $game = array();
      /*
        build game array to generate games
        two names per game then create next
      */
      $game = array_chunk($shuffled_array,2);

      //loop through the matches and insert into database
      foreach($game as $match){
        $pl1ID = $match[0];
        $pl2ID = $match[1];
        $round = 1;
      
        try{
          $st = $conn->prepare("INSERT INTO  `knockout_games` (`GameID`, `Player1ID`, `Player2ID`, `Round`) VALUES (?, ?, ?, ?);");
          $st->bind_param("iiii", $gameID, $pl1ID, $pl2ID, $round);
          $st->execute();
          $st->close();
        }catch(Exception $e){
          $stmt = $conn->prepare("DELETE FROM `game` WHERE id = ?");
          $stmt->bind_param("i", $gameID);
          $stmt->execute();
          $stmt->close();
          return false;
        }
      }
    }

    static function CleanUpTable($conn,$editGameID){
      //delete previous knockout game
      try{
        $stmt = $conn->prepare("DELETE FROM `knockout_games` WHERE `gameID` = ?");
        $stmt->bind_param("i", $editGameID);
        $stmt->execute();
        $stmt->close();
        return true;
      }catch(Exception $e){
        return false;
      }
    }

    function __construct($conn,$gameID){
      parent::__construct($conn, $gameID);
      //if the game already has a winner
      try{
          $this->winner = [];
          $st2 = $conn->prepare("SELECT `p`.`ID`,`p`.`Name`,`p`.`Lastname`
              FROM `competition_winners` `tw`
              INNER JOIN `game_players` `p` ON `p`.`ID` = `tw`.`WinnerID`
              WHERE `GameID` = ?;");
          $st2->bind_param("i", $gameID);
          $st2->execute();
          $result = $st2->get_result();
          while($row = $result->fetch_assoc()) {
            $this->winner[] = $row;
          }
          $st2->close();
      }catch(Exception $e){
          $this->winner = null;
      }

      //get the knockout rounds
      try{
          //get knockout players match view and info
          $this->knockoutGames =  array();
          $this->rounds = array();
          $st1 = $conn->prepare("SELECT `g`.`Name` AS `Game Name`,`g`.`StartDate`,`g`.`EndDate`,`ko`.`Player1ID`,`ko`.`Player2ID`,
                  `ko`.`MatchID`,`ko`.`Round`,
                  CONCAT(COALESCE(p1.`Name`,'Extra'),' ', COALESCE(p1.`Lastname`,'Player')) AS P1Name,
                  CONCAT(COALESCE(p2.`Name`,'Extra'),' ', COALESCE(p2.`Lastname`,'Player')) AS P2Name,`ko`.`Winner`
                  FROM game `g`
                  INNER JOIN `knockout_games` `ko` ON `ko`.`GameID` = `g`.`ID`
                  INNER JOIN `game_type` `gt` ON `gt`.`ID` = g.`TypeID`
                  LEFT JOIN game_players p1 ON ko.`Player1ID` = p1.`ID`
                  LEFT JOIN game_players p2 ON ko.`Player2ID` = p2.`ID`
                  WHERE `g`.`ID` =  ? 
                  GROUP BY `ko`.`Round`, `ko`.`Player1ID`,`ko`.`Player2ID`,`ko`.`Winner`;");
          $st1->bind_param("i", $gameID);
          $st1->execute();
          $result = $st1->get_result();
          while($row = $result->fetch_assoc()){
            $this->knockoutGames[] = $row;
            //if $rounds[$knockoutGames[0]["Round"]] isn't set, set to an empty array
            if(!isset($this->rounds[$row["Round"]])){
              $this->rounds[$row["Round"]] = array();
            }
            $this->rounds[$row["Round"]][] = $row;
          }
          $st1->close();
      }catch(Exception $e){
        $this->rounds = null;
        $this->knockoutGames = null;
      }

      //get the game type id and status
      try{
        $stmt = $conn->prepare("SELECT `Active` FROM `game` WHERE `ID` = ?");
        $stmt->bind_param("i", $gameID);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($this->active);
        $stmt->fetch();
        $stmt->close();
      }catch(Exception $e){
          /*
            Basically this statement checks if the game is active or not.
            If statement fails, then $this->active will be NULL, and no code after this try ... catch in the constructor will run
            and no scores board will be presented, only a error message.
            It won't cause problem because if $this->active === NULL will never match $this->active === 1 or $this->active === 0 ,
            as the espected result of this querie should be 1 or 0.
          */
        return false;
      }

      return true;
    }

    public function mainBoard(){
      echo"<div class='generalKnockoutView'>";
       //if the game is active
        if($this->active===1){
          if($this->winner === null){
            echo"<h3 style='margin-left:40%;'>* ERROR RETRIEVING GAME WINNER</h3></div>";
          }
          else if(count($this->winner) >= 1){
              echo "<div class='iziModal' id='iziModal' >
                      <div class='iziInternal'>
                          <img src='img/loader.gif' />
                          <b class='endingGame'>ENDING GAME</b>
                      </div>
                      </div>
                    <h2 class='winnerLabel'>TOURNAMENT HAS A WINNER</h2>
                    <div class='pulseWinner' style=display:block >
                      <p class='winnerName'>";
                      foreach($this->winner as $winn){
                          echo "<span class='ifWiner' id='".$winn['ID']."'>".$winn['Name']." ".$winn['Lastname']."</span>";
                      }
                      echo"</p></div></div><a class='endKoGame1'>END GAME</a>";
          }
          elseif(count($this->winner) == 0){
            echo"<div class='iziModal' id='iziModal'>
                    <div class='iziInternal'>
                      <img src='img/loader.gif' />
                      <b class='endingGame'>ENDING GAME</b>
                    </div>
                  </div>
                  <h3 class='warningUser'>Click To Select The Winner</h3>
                  <button class='editKoPreviousResult' data-prevhtml='Finish Editing Results'>Edit Previews Results</button>
                  <div class='pulseWinner'></div>";
                if($this->rounds === null){
                  echo"<div class='knockoutview'><h3 class='bMessage'>* ERROR RETRIEVING ROUNDS</h3></div></div>";
                }
                else{
                  foreach($this->rounds as $round => $roundInfo){
                    echo"<div id='knockOutRound".$round."' class='knockoutview'>";
                        foreach($roundInfo as $rond){
                          echo "<div id='container".$rond['MatchID']."' class='matchContainer'><p class='round'>Round: ".$round."</p>
                                  <h2 id='player".$rond['Player1ID']."' class='".(($rond['Winner'] == $rond['Player1ID'] && $rond['Winner'] != Null && $rond['P1Name']!="FREE PASS") ? 'selected' : ''). "' >".$rond['P1Name']."</h2> 
                                  <span><i>VS</i></span> 
                                  <h2 id='player".$rond['Player2ID']."' class='".(($rond['Winner'] == $rond['Player2ID'] && $rond['Winner'] != Null && $rond['P2Name']!="FREE PASS")? 'selected' : ''). "' >".$rond['P2Name']."</h2>
                                </div>";
                        }
                        echo"</div>";
                  }
                  echo"<div class='showNextRound'></div>
                      <div class='showWinner'></div>
                    </div></div>";
                }
          }
          echo"<br><a class='endKoGame' style='display:none;'>END GAME</a>";
        }
        else{
          echo"<div id='iziModal' class='iziModal'></div>
          <h3 class='warningUser'>GAME HISTORY</h3>";
          if($this->rounds == null){
            echo"<div class='knockoutview'>
            <h3 class='bMessage'>* ERROR RETRIEVING ROUNDS</h3></div>";
          }
          else{
            foreach($this->rounds as $round => $roundInfo){
              echo"<div id='knockOutRound".$round."' class='knockoutview'>";
                  foreach($roundInfo as $rond){
                    echo "<div id='container".$rond['MatchID']."' class='matchContainer1'><p class='round'>Round: ".$round."</p>
                            <h2 id='player".$rond['Player1ID']."' class='".(($rond['Winner'] == $rond['Player1ID'] && $rond['Winner'] != Null && $rond['P1Name']!="FREE PASS") ? 'selected' : ''). "' >".$rond['P1Name']."</h2> 
                            <span><i>VS</i></span> 
                            <h2 id='player".$rond['Player2ID']."'  class='".(($rond['Winner'] == $rond['Player2ID'] && $rond['Winner'] != Null && $rond['P2Name']!="FREE PASS")? 'selected' : ''). "' >".$rond['P2Name']."</h2>
                          </div>";
                  }echo"</div>";
            }
          }
          echo"<div class='buttonsDiv'>";
        }
        echo "<a id='knockoutBackBttn' class='button1' href='viewGames.php'>Back</a></div><br></body></html>";
    }
  }

  class GameWithLeaderBoard extends GameInfo{
    public $tLeader;
    public $gameID;

    function __construct($conn,$gameID){
      parent::__construct($conn, $gameID);
    }

    public function LeaderBoard(){
      if($this->tLeader == null){
        echo "<div class='currentLeaderDiv'><h3>* ERROR RETRIEVING GAME SCORES</h3></div>";
      }
      else{
        echo "<div class='currentLeaderDiv'>
              <table>
                <tr class='leaderBoard'>
                <th>Ranking</th>
                <th>Points</th>
                </tr>";
                //highlight only the first player with more points
                $first = true;
                $second = true;
                $third = true;
                foreach($this->tLeader as $player){
                  switch(true){
                    case $player['Sum'] == 0:
                        echo "<tr><td id=".$player['ID']." class='zeroPointPlayer'>".$player['Name']." ".$player['Lastname']."</td>
                                <td class='zeroPointPlayer'>".$player['Sum']."</td>";
                    break;
                   
                    case $player['Sum'] < 0:
                        echo "<tr><td id=".$player['ID']." class='-zeroPointPlayer'>".$player['Name']." ".$player['Lastname']."</td>
                                <td class='-zeroPointPlayer'>".$player['Sum']."</td>";
                    break;
                   
                    case $first == true && $player['Sum'] != 0:
                        echo "<tr><td id=".$player['ID']." class='CurrentLeader'>".$player['Name']." ".$player['Lastname']."</td>
                                <td class='CurrentLeader'>".$player['Sum']."</td>";
                            $first=false;
                    break;
                   
                    case $second == true:
                        echo "<tr><td id=".$player['ID']." class='SecondLeader'>".$player['Name']." ".$player['Lastname']."</td>
                                <td class='SecondLeader'>".$player['Sum']."</td> ";
                            $second = false;
                    break;
                   
                    case $third == true:
                        echo "<tr><td id=".$player['ID']." class='thirdLeader'>".$player['Name']." ".$player['Lastname']."</td>
                                <td class='thirdLeader'>".$player['Sum']."</td>";
                            $third =false;
                    break;
                   
                    default:
                        echo "<tr><td id=".$player['ID']." class='leaderList'>".$player['Name']." ".$player['Lastname']."</td>
                                <td class='leaderList'>".$player['Sum']."</td>
                            </tr>";
                    break;
                  }
                }
        echo "</tr></table></div>";
      }
    }
  }

  class AllVsAll extends GameWithLeaderBoard{
    private $players;
    private $playerScoreInfo;
    private $playerScoreInfoHist;
    private $baseScore;
    private $playersArray;
    private $attempt;
    private $minRange;
    private $maxRange;
    private $playerDayArray;
    private $tempScore;
    public $gameID;

    static function GetCompetitionLeader($conn,$gameID){
        //get all vs all game leaderBoard
        try{
            $leader= [];
            $stm2 = $conn->prepare("SELECT * FROM(
                SELECT pl.`ID`,pl.`Name`,pl.`Lastname`,COALESCE(SUM(`s`.`dailyScore`), 0) AS `Sum`
                FROM `game_players` `pl`
                LEFT JOIN `game_score` AS `s` ON `s`.`playerID` = `pl`.`ID`  AND `s`.`gameID` = ?
                WHERE JSON_SEARCH((SELECT `SelectedPlayers` FROM game WHERE ID = ?), 'one', `pl`.`ID`) IS NOT NULL
                GROUP BY pl.`ID`
                ORDER BY `s`.`date`)AS `Leader`
                ORDER BY `Sum` DESC,`Name`,`Lastname`;");
            $stm2->bind_param("ii", $gameID,$gameID);
            $stm2->execute();
            $result = $stm2->get_result();
            while($row = $result->fetch_assoc()) {
                $leader[] = $row;
            }
            return $leader[0]['ID'];
            $stm2->close();
        }catch(Exception $e){
            $leader = null;
        }
    }

    function __construct($conn,$gameID){
      parent::__construct($conn, $gameID);
      $this->gameID = $gameID;

      //get game leader
      try{
        $this->tLeader= [];
        $this->gameID = $gameID;
        $stm2 = $conn->prepare("SELECT * FROM(
            SELECT pl.`ID`,pl.`Name`,pl.`Lastname`,COALESCE(SUM(s.`dailyScore`), 0) AS `Sum`
            FROM `game_players` `pl`
            LEFT JOIN `game_score` AS `s` ON `s`.`playerID` = `pl`.`ID`  AND `s`.`gameID` = ?
            WHERE JSON_SEARCH((SELECT `SelectedPlayers` FROM game WHERE ID = ?), 'one', `pl`.`ID`) IS NOT NULL
            GROUP BY pl.`ID`
            ORDER BY `s`.`date`)AS `Leader`
            ORDER BY `Sum` DESC,`Name`,`Lastname`;");
        $stm2->bind_param("ii", $this->gameID,$this->gameID);
        $stm2->execute();
        $result = $stm2->get_result();
        while($row = $result->fetch_assoc()) {
          $this->tLeader[] = $row;
        } 
        $stm2->close();
      }catch(Exception $e){
        $this->tLeader = null;
      }

      //get all vs all player and score info
      try{
        $this->players = array();
        $this->playerScoreInfo = array();
        $this->baseScore;
        $stm1 = $conn->prepare("SELECT `p`.`ID`,p.`Name`,p.`Lastname`,s.`dailyScore` AS `Daily Score`,`s`.`scoreMetadata`,`s`.`date`,
            JSON_VALUE((SELECT `siteSettings` FROM game WHERE Id = ?), '$.BaseScore') AS BaseScore
            FROM `game_players` `p`
            LEFT JOIN `game_score` AS `s` ON `s`.`playerID` = `p`.`ID`  AND `s`.`gameID` = ?
            WHERE JSON_SEARCH((SELECT `SelectedPlayers` FROM game WHERE Id = ?), 'one', `p`.`ID`) IS NOT NULL 
            HAVING BaseScore IS NOT NULL
            ORDER BY `s`.`date`, `p`.`Name` ASC;");
        $stm1->bind_param("iii", $this->gameID,$this->gameID,$this->gameID);
        $stm1->execute();
        $result = $stm1->get_result();
        while($row = $result->fetch_assoc()){
          if(!isset($this->playerScoreInfo[$row["date"]])){
              $this->playerScoreInfo[$row["date"]] = array();
          }

          if(!isset($players[$row["ID"]])){
              $this->players[$row["ID"]] = $row["Name"]." ".$row['Lastname'];
          }
          
          if(!isset($baseScore[$row["BaseScore"]])){
              $this->baseScore = $row['BaseScore'];
          }
          $this->playerScoreInfo[$row["date"]][$row["ID"]] = $row["Daily Score"];
        }
      }catch(Exception $e){
        $this->playerScoreInfo = null;
        $this->players = null;
        $this->baseScore = null;
      }

      //get player,attempts,min and maxScore to enter day score
      try{
          //get temporary player score
          $this->playersArray = [];
          $this->attempt;
          $this->minRange;
          $this->maxRange;
          $stmt2 = $conn->prepare("SELECT * FROM(SELECT pl.`ID`,pl.`Name`,pl.`Lastname`,COALESCE(SUM(`s`.`dailyScore`), 0) AS `Sum`,ts.`DailyScore`,ts.`ScoreMetadata`,ts.`Date`,
                  JSON_VALUE((SELECT `siteSettings` FROM game WHERE Id = ?), '$.Attempts') AS Attempt,
                  JSON_VALUE((SELECT `siteSettings` FROM game WHERE Id = ?), '$.MinPointRange') AS MinRange,
                  JSON_VALUE((SELECT `siteSettings` FROM game WHERE Id = ?), '$.MaxPointRange') AS MaxRange
                  FROM `game_players` `pl`
                  LEFT JOIN `game_score` AS `s` ON `s`.`playerID` = `pl`.`ID`  AND `s`.`gameID` = ?
                  LEFT JOIN `game_temporary_score` `ts` ON `ts`.`playerID` = `pl`.`ID` AND `ts`.`date` = CURRENT_DATE AND `ts`.`gameID` = ?
                  WHERE JSON_SEARCH((SELECT `SelectedPlayers` FROM game WHERE ID = ?), 'one', `pl`.`ID`) IS NOT NULL
                  GROUP BY pl.`ID`
                  HAVING `Attempt` IS NOT NULL AND `MinRange` IS NOT NULL AND `MaxRange` IS NOT NULL
                  ORDER BY `s`.`date`)AS `Leading`
                  ORDER BY `Sum` DESC,`Name`,`Lastname`;");
          $stmt2->bind_param("iiiiii",$this->gameID, $this->gameID, $this->gameID, $this->gameID, $this->gameID, $this->gameID);
          $stmt2->execute();
          $result = $stmt2->get_result();
          while($row = $result->fetch_assoc()){
              $this->playersArray[] = $row;

              if(!isset($attempt[$row["Attempt"]])){
                  $this->attempt = $row['Attempt'];
              }
              if(!isset($minRange[$row["MinRange"]])){
                  $this->minRange = $row['MinRange'];
              }
              if(!isset($maxRange[$row["MaxRange"]])){
                  $this->maxRange = $row['MaxRange'];
              }
              $this->today = date("Y-m-d");
              $this->gameID = $gameID;
          }
          $stmt2->close();
      }
      catch(Exception $e){
          $this->playersArray = null;
          $this->attempt = null;
          $this->minRange = null;
          $this->maxRange = null;
      }

      //get player,attempts,min and maxScore to edit day score
      try{
          //get the players with their scores
          $this->playerDayArray = [];
          $date = isset($_GET['date']) ? $_GET['date'] :'' ;
          $stmt2 = $conn->prepare("SELECT `p`.`ID`,`p`.`Name`,`p`.`Lastname`,s.`Date`,
              `s`.`DailyScore` ,s.`ScoreMetadata` ,
              JSON_VALUE((SELECT `siteSettings` FROM game WHERE Id = ?), '$.Attempts') AS Attempt,
              JSON_VALUE((SELECT `siteSettings` FROM game WHERE Id = ?), '$.MinPointRange') AS MinRange,
              JSON_VALUE((SELECT `siteSettings` FROM game WHERE Id = ?), '$.MaxPointRange') AS MaxRange
              FROM `game_players` `p`
              LEFT JOIN `game_score` `s` ON `s`.`playerID` = `p`.`ID` AND `s`.`date` = ? AND `s`.`gameID` = ?
              WHERE JSON_SEARCH((SELECT `SelectedPlayers` FROM game WHERE Id = ?), 'one', `p`.`ID`) IS NOT NULL 
              HAVING `Attempt` IS NOT NULL AND `MinRange` IS NOT NULL AND `MaxRange` IS NOT NULL
              ORDER BY `p`.`Name` ASC;");
          $stmt2->bind_param("iiisii",  $gameID, $gameID, $gameID, $date, $gameID, $gameID);
          $stmt2->execute();
          $result = $stmt2->get_result();
          while($row = $result->fetch_assoc()){
              $this->playerDayArray[] = $row;
          }
          
          $stmt2->close();
      } catch(Exception $e){
          $this->playerDayArray = null;
      }

      //get the game type id and status
      try{
          $stmt = $conn->prepare("SELECT `Active` FROM `game` WHERE `ID` = ?");
          $stmt->bind_param("i",$this->gameID);
          $stmt->execute();
          $stmt->store_result();
          $stmt->bind_result($this->active);
          $stmt->fetch();
          $stmt->close();
      }catch(Exception $e){
          /*
              Basically this statement checks if the game is active or not.
              If statement fails, then $this->active will be NULL, and no code after this try ... catch in the constructor will run
              and no scores board will be presented, only a error message.
              It won't cause problem because if $this->active === NULL will never match $this->active === 1 or $this->active === 0 ,
              as the espected result of this querie should be 1 or 0.
          */
          return false;
      }

      //get all vs all player and score info history
      try{
          $this->historyPlayers = array();
          $this->playerScoreInfoHist = array();
          $stm1 = $conn->prepare("SELECT `p`.`ID`,p.`Name`,p.`Lastname`,s.`dailyScore` AS `Daily Score`,`s`.`scoreMetadata`,`s`.`date`
              FROM `game_players` `p`
              LEFT JOIN `game_score` AS `s` ON `s`.`playerID` = `p`.`ID`  AND `s`.`gameID` = ?
              WHERE JSON_SEARCH((SELECT `SelectedPlayers` FROM game WHERE Id = ?), 'one', `p`.`ID`) IS NOT NULL 
              ORDER BY `s`.`date`, `p`.`Name` ASC;");
          $stm1->bind_param("ii", $this->gameID,$this->gameID);
          $stm1->execute();
          $result = $stm1->get_result();
          while($row = $result->fetch_assoc()){
              if(!isset($this->playerScoreInfoHist[$row["date"]])){
                  $this->playerScoreInfoHist[$row["date"]] = array();
              }

              if(!isset($players[$row["ID"]])){
                  $this->historyPlayers[$row["ID"]] = $row["Name"]." ".$row['Lastname'];
              }

              $this->playerScoreInfoHist[$row["date"]][$row["ID"]] = $row["Daily Score"];
          }
      }catch(Exception $e){
          $this->playerScoreInfoHist = null;
          $this->historyPlayers = null;
      }

          //If scores were added but not ‘saved’ on a given day, it saved into game_score table after 1 day
      try{
        $allTScore = [];
        $dateToday = date('Y-m-d');
        $stm = $conn->prepare("SELECT * FROM `game_temporary_score` WHERE `DateScoreEntered` < ? ;");
        $stm->bind_param("s",$dateToday);
        $stm->execute();
        $result = $stm->get_result();
        while($row = $result->fetch_assoc()) {
            $allTScore[] = $row;
        }
        $stm->close();
      }catch(Exception $e){
        $allTScore = null;
      }

      // check if not saved temporary scores are found
      if(count($allTScore) > 0){
          foreach($allTScore as $notSavedScore){
          $playerId = $notSavedScore["PlayerID"];
          $gameId = $notSavedScore["GameID"];
          $Date = $notSavedScore["Date"];
          $dailyScore = $notSavedScore["DailyScore"];
          $scoreMetadata = $notSavedScore["ScoreMetadata"];

          try{
              //insert into game_score table
              $stmt = $conn->prepare("INSERT INTO `game_score` (`PlayerID`,`GameID`,`Date`, `DailyScore`,`ScoreMetadata`) VALUES (?, ?, ?, ?, ?)");
              $stmt->bind_param("iisis", $playerId, $gameId, $Date, $dailyScore, $scoreMetadata);
              $stmt->execute();
              $stmt->close();
          }catch(Exception $e){
              return false;
          }

          try{
              //after insert into game score delete from temporary score table
              $stmt = $conn->prepare("DELETE FROM `game_temporary_score` WHERE gameid = ? and `date`= ?");
              $stmt->bind_param("is",$gameId,$Date);
              $stmt->execute();
              $stmt->close();
          }catch(Exception $e){
              return false;
          }
          }
      }

      //if temporary score exists for a game
      try{
        $this->tempScore= [];
        $this->gameID = $gameID;
        $stm2 = $conn->prepare("SELECT DISTINCT `date` FROM `game_temporary_score` WHERE `GameID` = ? ORDER BY `Date` ASC");
        $stm2->bind_param("i", $gameID);
        $stm2->execute();
        $result = $stm2->get_result();
        while($row = $result->fetch_assoc()) {
          $this->tempScore[] = $row;
        }
        $stm2->close();
      }catch(Exception $e){
        $this->tempScore = null;
      }
      return true;
    }

    static function CleanUpTable($conn,$editGameID){
    }

    static function SetupGameType($conn,$editGameID,$selectedPlayers){
    }
    
    public function MainBoard(){
      $today = date("Y-m-d");
      $tscoreArray = [];
      echo "<div class='tournamentDiv'>";
          //checks if there's temporary scores
            if(count($this->tempScore) > 0){
              echo "<div class='ifTempScoreExist'>
                    <strong class='warning'>WARNING !! </strong><b> SCORES EXIST IN THE TEMPORARY TABLE THAT HAVE NOT BEEN PUT LIVE ON THE:&nbsp;  </b>";
                    foreach($this->tempScore as $tScore){
                      array_push($tscoreArray,$tScore['date']);
                    }
                    echo "<i class='tempScoreDate' >".implode(",",$tscoreArray)."</i>&nbsp;<button class='addExistingTempScore'>ADD LIVE</button>
                    </div><br>";
            }
      //if the game is active
      if($this->active === 1){
          if($this->playerScoreInfo === null){
            echo "<h3 style='margin-left:35%;'>* ERROR RETRIEVING PLAYERS & SCORE INFO</h3>";
          }
          else{
            echo "<div id='iziModal' class='iziModal'>
                    <div class='iziInternal'>
                        <img src='img/loader.gif' />
                        <b class='endingGame'>ENDING GAME</b>
                    </div>
                  </div>
                  <div id='iziModalInsertTempScore' class='iziModal'>
                    <div class='iziInternal'>
                        <img src='img/loader.gif' />
                        <b class='endingGame'>INSERTING TEMPORARY SCORES LIVE</b>
                    </div>
                  </div>
            <div class='playerAndPointsDiv'>
            <div class='playerList'>
            <div class='rTable'>
            <div class='rTableRow'>
            <div class='rTableHead'>Player Name</div>
            </div>";
            foreach($this->players as $playerid => $playerName){
              echo "<div class='rTableRow'><div id='playerName' class='rTableCell'>".$playerName."</div></div>";
            }
            echo "</div></div>
                <div class='playerPoints' id='playerPoints'>
                <div class='rTable'>
                <div class='rTableRow'>";
                foreach($this->playerScoreInfo as $gdates => $pArray){
                    $dayOfWeek = date("l jS", strtotime($gdates));
                    $timestamp = strtotime($gdates);
                    if($gdates !=""){
                      echo "<div class='rTableHead'>".$dayOfWeek."
                      <br>
                      <a data-date='".$gdates."' class='editDayPointBttn' href ='gameView.php?processType=editGameDay&gameID=".$this->gameID."&date=".$gdates."'>Edit</a>
                      </div>";
                    }else{
                      echo "<div class='rTableHead'>No Points Added</div>";
                    }
                }
                echo"</div>";
                foreach($this->players as $playerid => $playerName){
                    echo "<div class='rTableRow'>";
                    foreach($this->playerScoreInfo as $d){
                      //checks if the player has enter a value,even if null
                      if (array_key_exists($playerid, $d)) {
                        if($d[$playerid] === null){
                          //if the value is null
                          echo "<div class='rTableCell'><i>".$this->baseScore."</i></div>";
                        }
                        else{
                          echo "<div class='rTableCell'>".$d[$playerid]."</div>";
                        }
                      }//if there's no value entered
                      else{
                        echo "<div class='rTableCell'><i>-</i></div>";
                      }
                    }
                    echo "</div>";
                }
                echo "</div></div></div>
                    <br><div class='buttonsDiv'>
                    <a id='addDailyPoints' class='addPoints' href='gameView.php?processType=todayGame&todayGameID=".$this->gameID."' />Add Daily Points</a>
                    <a class='endAllVsAllGame' >End Game</a>";
          } 
      }
      else{
        if($this->playerScoreInfoHist === null){
          echo "<h3 style='margin-left:42%;'>* ERROR RETRIEVING PLAYERS & SCORE INFO</h3>";
        }
        else if( $this->historyPlayers === null){
          echo "<h4 class='bMessage' style='margin-left:42%;'>* ERROR LOADING PLAYERS INFO</h4>";
        }
        else{
            echo "<div id='iziModal' class='iziModal'></div>
            <div class='playerAndPointsDiv'>
            <div class='playerList'>
            <div class='rTable'>
            <div class='rTableRow'>
            <div class='rTableHead'>Player Name</div>
            </div>";
            foreach( $this->historyPlayers as $playerid => $playerName){
              echo " <div class='rTableRow'><div id='playerName' class='rTableCell'>".$playerName."</div></div>";
            }
            echo "</div></div>
                <div class='playerPoints' id='playerPoints'>
                <div class='rTable'>
                <div class='rTableRow'>";
                foreach($this->playerScoreInfoHist as $gdates => $pArray){
                    $dayOfWeek = date("l jS", strtotime($gdates));
                    $timestamp = strtotime($gdates);
                    if($gdates !=""){
                      echo "<div class='rTableHead'>".$dayOfWeek."</div>";
                    }else{
                    echo "<div class='rTableHead'>No Points Added</div>";
                    }
                }
                echo"</div>";
                    foreach( $this->historyPlayers as $playerid => $playerName){
                      echo "<div class='rTableRow'>";
                      foreach($this->playerScoreInfoHist as $d){
                        if($d[$playerid] === null){
                            echo "<div class='rTableCell'><i>".$this->baseScore."</i></div>";
                        }else{
                            echo "<div class='rTableCell'>".$d[$playerid]."</div>";
                        }
                      }
                      echo "</div>";
                    }
                    echo "</div></div></div>";
        } 
        echo "<br><div class='buttonsDiv'>";
      }
      echo "<a id='allVsAllBackBttn' class='button1' href='viewGames.php'>Back</a></div></div>";
    }

    public function EnterScoreBoard(){
      switch($_GET['processType'] ?? "unknown"){
        case 'editGameDay':
          $date = $_GET['date'];
          echo "<div class='ViewGame' id=".$this->gameID." data-date=".$date.">";
          if($this->playerDayArray === null){
            echo  "<h3>* ERROR RETRIEVING PLAYER & SCORE INFO</h3>
            <div class='buttonsDiv'>";
          }
          else{
            echo "<div class='iziModal' id='iziModal'>
                    <div class='iziInternal'>
                        <img src='img/loader.gif' />
                        <b class='endingGame'>UPDATING ALL GAME SCORES</b>
                    </div>
                  </div>
            <div class='badMessageEmptyScore' ></div>
            <form action='' method='post'>
            <table>
            <tr class='gameinfosHead'>
            <th>Players</th>
            <th>Date: &nbsp;".$date."</th>
            </tr>";
            foreach($this->playerDayArray as $player){
              //if the player has a score for the day, will store it here
              $dailyScore = json_decode($player['DailyScore']);
              $scoreMetadata = json_decode($player['ScoreMetadata']);
              echo "<tr class='gameInfos'>
              <td class='playerInfo' id='playerID".$player['ID']."'>".$player['Name']." ".$player['Lastname']."</td>
              <td><p class='errorMsg'></p>
              <div id='myDiv".$player['ID']."' class='addEditButtonDiv'>";
              if(is_null($scoreMetadata) == true ){
                $count = 1;
                echo "<div class='pointsInput'>
                        <b class='showDailyScore' id='".$player['ID']."'></b>";
                while($count <=  $this->attempt){
                    echo "<input type='number' placeholder='Attempt ".$count."' id='attempt".$player['ID']."_".$count."' name='score[".$player['ID']."][attempt".$count."]' class='scoreAttempt' min='".$this->minRange."' max='".$this->maxRange."' /> ";
                    $count++;
                }
                echo "</div>";
                echo "<div class='btncontainer'>
                <span class='ajaxLoader' id='ajaxLoader' style='display:none;'><img src='img/ajax-loader.gif' alt='Smiley face'></span>
                        <input type='button' id='addSinglePointsDay' class='addSinglePointsDay' name='addSinglePointsDay' value='Add Points' />
                        <input type='button' id= 'editSinglePointsDay' class='editSinglePointsDay' name='editSinglePointsDay' value='Edit Points' style='display: none;'/>
                        <input type='button' id='updateSinglePointsDay' class='updateSinglePointsDay' name='updateSinglePointsDay' value='Update Points' style='display: none;'/>
                        </div>
                        </div>
                        </td>
                    </tr>";
              }
              else{
                echo "<div class='pointsInput'><b class='showDailyScore' id='".$player['ID']."'>".$dailyScore."</b>";
                $count = 1;
                while($count <= $this->attempt){
                  foreach($scoreMetadata as $key => $value){
                    echo "<input  type='number'  style='display:none;' placeholder='Attempt ".$count."'  id='attempt".$player['ID']."_".$count."' name='score[".$player['ID']."][attempt".$count."]' class='scoreAttempt' min='".$this->minRange."' max='".$this->maxRange."' value='".$value."'>";
                    $count++;
                  }
                }
                echo "</div>";
                echo" <div class='btncontainer'>
                <span class='ajaxLoader' id='ajaxLoader' style='display:none;'><img src='img/ajax-loader.gif' alt='Smiley face'></span>
                        <input type='button' class='addSinglePointsDay' name='addSinglePointsDay' value='Add Points' style='display: none;'/>
                        <input type='button' class='editSinglePointsDay' name='editSinglePointsDay' value='Edit Points' />
                        <input type='button' class='updateSinglePointsDay' name='updateSinglePointsDay' value='Update Points' style='display: none;'/>
                    </div>";
              } 
              echo "</td></tr>"; 
            }
            echo"</table><br><div class='buttonsDiv'><a id='updateAllEditedPoints' class='done' >Edit All Scores</a> ";
          }
          echo "<a class='backBtnGameView' id='backTotalScore' href='displayGame.php?processType=game&gameID=".$this->gameID." '>Back</a></div><br></form>";
        break;

        default:
          echo "<div class='ViewGame' id='".$this->gameID."'>";
          if($this->playersArray === null){
            echo "<h3>* ERROR RETRIEVING GAME INFO</h3><div class='buttonsDiv'>";
          }
          else{
              echo "<div class='iziModal' id='iziModal'>
                      <div class='iziInternal'>
                          <img src='img/loader.gif' />
                          <b class='endingGame'>INSERTING ALL GAME SCORES</b>
                      </div>
                    </div>
              <div class='badMessageEmptyScore' ></div>
              <form action='' method='post'>
              <table id='myTable'>
              <tr class='gameinfosHead'>
              <th>Players</th>
              <th>Date: <input type='date' id='dateScore' name='dateScore' value='".$this->today."'></th></tr>";
              foreach($this->playersArray as $player){
                //if the player has a score for the day, will store it here
                $dailyScore = json_decode($player['DailyScore']);
                $TempScore = json_decode($player['ScoreMetadata']);
                $scoreDate = $player['Date'];
                echo "<tr class='gameInfos'>
                <td class='playerInfo' data-sum='".$player['Sum']."' id='playerID".$player['ID']."'>".$player['Name']." ".$player['Lastname']."</td>
                <td><p class='errorMsg'></p>
                <div id='myDiv".$player['ID']."' class='addEditButtonDiv'>";
                if(is_null($TempScore) === true ){
                  $count = 1;
                  echo "<div class='pointsInput' id='pointsInput' data-date='".$scoreDate."'>
                          <b class='showDailyScore'  id='".$player['ID']."'></b>";
                  while($count <= $this->attempt){
                    echo "<input type='number' placeholder='Attempt ".$count."' id='attempt".$player['ID']."_".$count."' name='score[".$player['ID']."][attempt".$count."]' class='scoreAttempt' min='".$this->minRange."' max='".$this->maxRange."'  /> ";
                    $count++;
                  }
                  echo "</div>";
                  echo "<div class='btncontainer'>
                          <span class='ajaxLoader' id='ajaxLoader' style='display:none;'><img src='img/ajax-loader.gif' alt='Smiley face'></span>
                          <input type='button' id='addSinglePoints' class='addSinglePoints' name='addSinglePoints' value='Add Points' />
                          <input type='button' id='editSinglePoints' class='editSinglePoints' name='editSinglePoints' value='Edit Points' style='display: none;'/>
                          <input type='button' id='updateSinglePoints' class='updateSinglePoints' name='updateSinglePoints' value='Update Points' style='display: none;'/>
                          </div>
                          </div></td></tr>";
                }
                else{
                  echo "<div class='pointsInput' id='pointsInput' data-date='".$scoreDate."'>
                          <b class='showDailyScore'  id='".$player['ID']."'>".$dailyScore."</b>";
                  $count = 1;
                  while($count <= $this->attempt){
                    foreach($TempScore as $key => $value){
                      echo "<input style='display:none;' placeholder='Attempt ".$count."' type='number' id='attempt".$player['ID']."_".$count."' name='score[".$player['ID']."][attempt".$count."]' class='scoreAttempt' min='".$this->minRange."' max='".$this->maxRange."' value='".$value."'  />";
                      $count++;
                    }
                    echo "</div>";
                  }
                  echo" <div class='btncontainer'>
                          <span class='ajaxLoader' id='ajaxLoader' style='display:none;'><img src='img/ajax-loader.gif' alt='Smiley face'></span>
                          <input type='button' id='addSinglePoints' class='addSinglePoints' name='addSinglePoints' value='Add Points' style='display: none;'/>
                          <input type='button' id='editSinglePoints' class='editSinglePoints' name='editSinglePoints' value='Edit Points' />
                          <input type='button' id='updateSinglePoints' class='updateSinglePoints' name='updateSinglePoints' value='Update Points' style='display: none;'/>
                          </div>";
                } 
                echo "</td></tr>"; 
              }
              echo"</table><br><div class='buttonsDiv'><a class='done'  name='done' id='updateAllPoints' >Enter All Scores</a>";
          }
          echo " <a class='backBtnGameView' id='backBtnGameView' href='displayGame.php?processType=game&gameID=".$this->gameID." '>Back</a></div><br></form>";
        break;
      }
    }
  }

  class Tournament extends GameWithLeaderBoard{
    private $tRounds;
    private $tCurrentRound;
    private $cRound;
    private $nRounds;
    private $winners;
    private $nWinnerPerRound;
    private $roundX; 
    public $gameID;

    static function GetCompetitionLeader($conn,$gameID){
      //get all vs all game leaderBoard
      try{
        $leader= [];
        $stm2 = $conn->prepare("SELECT * FROM(
            SELECT pl.`ID`,pl.`Name`,pl.`Lastname`,COALESCE(SUM(`s`.`dailyScore`), 0) AS `Sum`
            FROM `game_players` `pl`
            LEFT JOIN `tournament_score` AS `s` ON `s`.`playerID` = `pl`.`ID`  AND `s`.`gameID` = ?
            WHERE JSON_SEARCH((SELECT `SelectedPlayers` FROM game WHERE ID = ?), 'one', `pl`.`ID`) IS NOT NULL
            GROUP BY pl.`ID`
            ORDER BY `s`.`date`)AS `Leader`
            ORDER BY `Sum` DESC,`Name`,`Lastname`;");
        $stm2->bind_param("ii", $gameID,$gameID);
        $stm2->execute();
        $result = $stm2->get_result();
        while($row = $result->fetch_assoc()) {
            $leader[] = $row;
        }
        return $leader[0]['ID'];
        $stm2->close();
      }catch(Exception $e){
        $leader = null;
        return $leader;
      }
    }

    static function CreateTournament($conn, $name, $game_typeID, $startDate, $endDate, $passedSIteSettings, $date , $selectedPlayers){
      $newGameID =   parent::CreateTournament($conn, $name, $game_typeID, $startDate, $endDate, $passedSIteSettings, $date , $selectedPlayers);
      static::SetupGameType($conn,$newGameID,$selectedPlayers);
    }

    static function SetupGameType($conn,$gameID,$selectedPlayers){
      $gameID = $gameID;
      $playersID = $selectedPlayers;
      $updatedSelectedPlayers = json_encode( $playersID);
      $playerIds = implode(",",$playersID);

      //get data from players table
      try{
        $playerArray = [];
        $stmt2 = $conn->prepare("SELECT * FROM `game_players` `p` WHERE FIND_IN_SET(`p`.`ID`, ?);");
        $stmt2 ->bind_param("s",$playerIds);
        $stmt2->execute();
        $result = $stmt2->get_result();
        while($row = $result->fetch_assoc()) {
          $playerArray[$row['ID']] = $row;
        }
        $stmt2->close();
      }catch(Exception $e){
        $stmt = $mysqli->prepare("DELETE FROM `game` WHERE id = ?");
        $stmt->bind_param("i", $gameID);
        $stmt->execute();
        $stmt->close();
        return false;
      }

      $teams = [];
      foreach($playerArray as $p){
        array_push($teams,$p['ID']);
      }

      //call function Fixture
      $fixOdd = new Fixture($teams);
      $games = $fixOdd->GetSchedule();
      $i = 1;

      foreach($games as $rounds){
        foreach($rounds as $match){
          $player1ID = $match[0];
          $player2ID = $match[1];

          if($match[0] === "free this round" || $match[1] === "free this round"){
            $winner = "No Winner";
            $p1Score = 0;
            $p2Score = 0;

            try{
              $st = $conn->prepare("INSERT INTO  `tournament_games` (`GameID`, `Player1ID`, `Player2ID`, `Round`, `Winner`, `ResultP1`, `ResultP2`) VALUES (?, ?, ?, ?, ?, ?, ?);");
              $st->bind_param("iiiisii", $gameID, $player1ID, $player2ID, $i, $winner, $p1Score, $p2Score);
              $st->execute();
              $st->close();
            }catch(Exception $e){
              $stmt = $conn->prepare("DELETE FROM `game` WHERE id = ?");
              $stmt->bind_param("i", $gameID);
              $stmt->execute();
              $stmt->close();
              return false;
            }
          }
          else{
            try{
              $st1 = $conn->prepare("INSERT INTO  `tournament_games` (`GameID`, `Player1ID`, `Player2ID`, `Round`) VALUES (?, ?, ?, ?);");
              $st1->bind_param("iiii",$gameID, $player1ID, $player2ID, $i);
              $st1->execute();
              $st1->close();
            }catch(Exception $e){
              $stmt = $mysqli->prepare("DELETE FROM `game` WHERE id = ?");
              $stmt->bind_param("i", $gameID);
              $stmt->execute();
              $stmt->close();
              return false;
            }
          }
        }
        $i++;
      }
    }

    static function CleanUpTable($conn,$editGameID){
      try{
        $stmt1 = $conn->prepare("DELETE FROM `tournament_games` WHERE `gameID` = ?");
        $stmt1->bind_param("i", $editGameID);
        $stmt1->execute();
        $stmt1->close();
        return true;
      }catch(Exception $e){
          return false;
      }
    }

    function __construct($conn,$gameID){
      parent::__construct($conn, $gameID);
      //get tournament rounds
      //get league draw info
      try{
        $this->tRounds = array();
        $st1 = $conn->prepare("SELECT `g`.`Name` AS `Game Name`,`g`.`StartDate`,`g`.`EndDate`,`lg`.`Player1ID`,`lg`.`Player2ID`,
                `lg`.`MatchID`,`lg`.`Round`,
                CONCAT(COALESCE(p1.`Name`,'FREE'),' ', COALESCE(p1.`Lastname`,'THIS ROUND')) AS P1Name,
                CONCAT(COALESCE(p2.`Name`,'FREE'),' ', COALESCE(p2.`Lastname`,'THIS ROUND')) AS P2Name,`lg`.`Winner`
                FROM game `g`
                INNER JOIN `tournament_games` `lg` ON `lg`.`GameID` = `g`.`ID`
                INNER JOIN `game_type` `gt` ON `gt`.`ID` = g.`TypeID`
                LEFT JOIN game_players p1 ON lg.`Player1ID` = p1.`ID` AND lg.`Player1ID` > 0
                LEFT JOIN game_players p2 ON lg.`Player2ID` = p2.`ID` AND lg.`Player2ID` > 0
                WHERE `g`.`ID` =  ? 
                GROUP BY `lg`.`Round`, `lg`.`Player1ID`,`lg`.`Player2ID`,`lg`.`Winner`;");
        $st1->bind_param("i", $gameID);
        $st1->execute();
        $result = $st1->get_result();
        while($row = $result->fetch_assoc()) {
          //if $rounds["Round"] isn't set, set to an empty array
          if(!isset( $this->tRounds[$row["Round"]])){
              $this->tRounds[$row["Round"]] = array();
          }
          $this->tRounds[$row["Round"]][] = $row;
        }
        $st1->close();
      }catch(Exception $e){
        $this->tRounds = null;
      }
      
      $this->gameID = $gameID;
      $this->winners = [];
      $this->nWinnerPerRound = array();
      foreach($this->tRounds as $round){ 
        foreach($round as $games){
          //check number of winners per round
          if($games['Winner'] != NULL || $games['Winner'] === "No Winner"){
            array_push($this->nWinnerPerRound,$games['Winner']);
          }
        }
        //get the current round
        if(count($this->nWinnerPerRound) != count($round)){
          $roundX = $games['Round'];
          break;
        }
      }

      $nRounds = count($this->tRounds);
      $roundX=1;
      if(count($this->nWinnerPerRound) === count($round)){
        $cRound = $roundX + 1;
      }
      else if($roundX === $nRounds){
        $cRound = $roundX;
      }
      else{
        $cRound = $roundX;
      }

      //get the current rounds matches
      try{
        $this->tCurrentRound = array();
        $st2 = $conn->prepare("SELECT `lg`.`Player1ID`,`lg`.`Player2ID`,
                `lg`.`MatchID`,`lg`.`Round`,
                CONCAT(COALESCE(p1.`Name`,'FREE'),' ', COALESCE(p1.`Lastname`,'THIS ROUND')) AS P1Name,
                CONCAT(COALESCE(p2.`Name`,'FREE'),' ', COALESCE(p2.`Lastname`,'THIS ROUND')) AS P2Name, lg.`ResultP1`, lg.`ResultP2`,`lg`.`Winner`
                FROM game `g`
                INNER JOIN `tournament_games` `lg` ON `lg`.`GameID` = `g`.`ID`
                LEFT JOIN game_players p1 ON lg.`Player1ID` = p1.`ID` AND lg.`Player1ID` > 0
                LEFT JOIN game_players p2 ON lg.`Player2ID` = p2.`ID` AND lg.`Player2ID` > 0
                WHERE `g`.`ID` =  ? and lg.Round = ?
                GROUP BY `lg`.`Round`, `lg`.`Player1ID`,`lg`.`Player2ID`,`lg`.`Winner`;");
        $st2->bind_param("ii", $gameID, $cRound);
        $st2->execute();
        $result = $st2->get_result();
        while($row = $result->fetch_assoc()) {
          //if $CurrentRound["Round"] isn't set, set to an empty array
          if(!isset($this->tCurrentRound[$row["Round"]])){
            $this->tCurrentRound[$row["Round"]] = array();
          }
          $this->tCurrentRound[$row["Round"]][] = $row;
        }
        $st2->close();
      }catch(Exception $e){
        $this->tCurrentRound = null;
      }

      //get the game status
      try{
        $stmt = $conn->prepare("SELECT `Active` FROM `game` WHERE `ID` = ?");
        $stmt->bind_param("i", $gameID);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($this->active);
        $stmt->fetch();
        $stmt->close();
      }catch(Exception $e){
        /*
          Basically this statement checks if the game is active or not.
          If statement fails, then $this->active will be NULL, and no code after this try ... catch in the constructor will run
          and no scores board will be presented, only a error message.
          It won't cause problem because if $this->active === NULL will never match $this->active === 1 or $this->active === 0 ,
          as the espected result of this querie should be 1 or 0.
        */
        return false;
      }

      //get tournament leaderBoard
      try{
        $this->tLeader= [];
        $this->gameID = $gameID;
        $stm2 = $conn->prepare("SELECT * FROM(
            SELECT pl.`ID`,pl.`Name`,pl.`Lastname`,COALESCE(SUM(s.`dailyScore`), 0) AS `Sum`
            FROM `game_players` `pl`
            LEFT JOIN `tournament_score` AS `s` ON `s`.`playerID` = `pl`.`ID`  AND `s`.`gameID` = ?
            WHERE JSON_SEARCH((SELECT `SelectedPlayers` FROM game WHERE ID = ?), 'one', `pl`.`ID`) IS NOT NULL
            GROUP BY pl.`ID`
            ORDER BY `s`.`date`)AS `Leader`
            ORDER BY `Sum` DESC,`Name`,`Lastname`;");
        $stm2->bind_param("ii", $this->gameID,$this->gameID);
        $stm2->execute();
        $result = $stm2->get_result();
        while($row = $result->fetch_assoc()) {
        $this->tLeader[] = $row;
        }
        $this->tLeader;
        $stm2->close();
      }catch(Exception $e){
          $this->tLeader = null;
      }

      return true;
    }

    public function MainBoard(){
      echo "<div class='activeGameDivTour' data-area = 'home'>";
       //if the game is active
      if($this->active ===1){
        echo "<div class='iziModal' id='iziModal'>
                <div class='iziInternal'>
                    <img src='img/loader.gif' />
                    <b class='endingGame'>ENDING GAME</b>
                </div>
              </div>
        <div class='badMessageEmptyScore'></div>
        <div class='jornada'>";
        if($this->tCurrentRound === null){
          echo"<h3 class='bMessage'>* ERROR LOADING CURRENT ROUND</h3></div>";
        }
        else{
          foreach ($this->tCurrentRound as $roun){
            echo "ROUND : ".$roun[0]['Round']."</div>";
          }
        }
        echo"<div class='showRounds'>";
        if($this->tRounds === null){
          echo"<h3 class='bMessage'>* ERROR LOADING ROUNDS</h3>";
        }
        else{
          foreach($this->tRounds as $round => $roundInfo){ 
            echo"<div class='rounds' id='".$round."' >";
                  if($round > 1 && $round <= 13){
                    echo" <div class='border_left'></div>";
                  }
            echo "$round</div>";
          }
        }
        echo"</div><br>
        <div class='matches'></div>";
        if($this->tCurrentRound === null){
            echo"<h3>ERROR LOADING CURRENTE MATCH ROUNDS</h3><div class='buttonsDiv'>";
        }
        else{ 
          foreach ($this->tCurrentRound as $rounds){ 
            foreach($rounds as $games){
              echo "<div class ='game' id='".$games['MatchID']."' round='".$games['Round']."'>";
              if($games['P1Name'] === "FREE THIS ROUND" || $games['P2Name'] === "FREE THIS ROUND" ){
                echo "<div class='homeTeam'><p class='freeRound'>".$games['P1Name']."</p></div> 
                      <div class='awayTeam'><p class='freeRound'>".$games['P2Name']."</p></div>";
              }
              else if($games['ResultP1'] != "" || $games['ResultP2'] != "" || $games['ResultP1'] == "No Winner" || $games['ResultP2'] == "No Winner"){
                echo "<div class='homeTeam'><p class='pName'>".$games['P1Name']."</p>
                          <input type='number'  maxlength='3' id='".$games['Player1ID']."' class='tournScoreHome' style='display:none' required='required'/>
                      </div> 
                      <div class='results'><p class='homeResults'>".$games['ResultP1']."</p> : <p class='awayResults'>".$games['ResultP2']."</p></div>
                      <div class = 'vs' style ='display:none'> vs </div>
                      <div class='awayTeam'>
                        <input type='number' maxlength='3'  id='".$games['Player2ID']."'  class='tournScoreAway'  style='display:none' required='required'/>
                        <p class='pName'>".$games['P2Name']."</p>
                      </div>
                      <button type='button' class='enterTournPoints' style='display:none'>Submit</button>
                      <button type='button' class='editTourPoint'>Edit</button> 
                      <button type='button' class='updateTourPoint' style='display:none'>Update</button>";
              }
              else{
                echo "<div class='homeTeam'> <p class='pName'>".$games['P1Name']."</p>
                        <input type='number'  maxlength='3' id='".$games['Player1ID']."'  class='tournScoreHome' required='required' />
                      </div> 
                      <div class='results' style='display:none'></div>
                      <div class='vs'> vs </div>
                      <div class='awayTeam'><input type='number'  maxlength='3'  id='".$games['Player2ID']."'  class='tournScoreAway' required='required'/>
                      <p class='pName'>".$games['P2Name']."</p>
                      </div>
                    <button type='button' class='enterTournPoints'>Submit</button>
                    <button type='button' class='editTourPoint' style='display:none'>Edit</button>
                    <button type='button' class='updateTourPoint' style='display:none'>Update</button>";
              }
              echo "</div>";
            }
          }
          echo"<br><div class='buttonsDiv'> <a class='endTournGame'>End Game</a>";
        }
      }
      else{
        echo "<div id='iziModal' class='iziModal'></div>
              <div class='badMessageEmptyScore'></div>
              <div class='jornada'>";
            if($this->tCurrentRound === null){
                echo"<h3 class='bMessage'>* ERROR LOADING CURRENT ROUND</h3>";
            }
            else{
              foreach ($this->tCurrentRound as $roun){
                echo "ROUND : ".$roun[0]['Round'];
              }
            }
            echo"</div><div class='showRounds'>";
            if($this->tRounds === null){
              echo"<h3 class='bMessage'>* ERROR LOADING ROUNDS</h3>";
            }
            else{
              foreach($this->tRounds as $round => $roundInfo){ 
                echo"<div class='historyRounds' id='".$round."' >";
                      if($round > 1 && $round <= 13){
                      echo" <div class='border_left'></div>";
                      }
                echo "$round</div>";
              }
            }
            echo"</div><br>
            <div class='matches'></div>";
            if($this->tCurrentRound === null){
              echo" <h3>ERROR LOADING CURRENTE MATCH ROUNDS</h3>";
            }
            else{ 
              foreach ($this->tCurrentRound as $rounds){ 
                foreach($rounds as $games){
                  echo "<div class ='game' id='".$games['MatchID']."' round='".$games['Round']."'>";
                  if($games['P1Name'] === "FREE THIS ROUND" || $games['P2Name'] === "FREE THIS ROUND" ){
                    echo "<div class='homeTeam'><p class='freeRound'>".$games['P1Name']."</p></div> 
                          <div class='awayTeam'><p class='freeRound'>".$games['P2Name']."</p></div>";
                  }
                  else if($games['ResultP1'] != "" || $games['ResultP2'] != "" || $games['ResultP1'] == "No Winner" || $games['ResultP2'] == "No Winner"){
                    echo "<div class='homeTeam'><p class='pName'>".$games['P1Name']."</p></div> 
                          <div class='results'>
                            <p class='homeResults'>".$games['ResultP1']."</p> : <p class='awayResults'>".$games['ResultP2']."</p></div>
                          <div class = 'vs' style ='display:none'> vs </div>
                          <div class='awayTeam'><p class='pName'>".$games['P2Name']."</p></div>";
                  }
                  else{
                    echo "<div class='homeTeam'><p class='pName'>".$games['P1Name']."</p> </div> 
                          <div class='results' style='display:none'></div>
                          <div class='vs'> vs </div>
                          <div class='awayTeam'><p class='pName'>".$games['P2Name']."</p></div>";
                  }
                  echo "</div>";
                }
              }
            }
        echo"<br><div class='buttonsDiv'>";
      }
      echo "<a id='tournamentBackBttn' class='backIndex' href='viewGames.php'>Back</a></div><br></div>";
    }
  }
