<?php
  require_once 'includes/dbConnnection.php';
  require_once 'includes/header.php';
  require_once 'classes/allClasses.php';
  require_once 'includes/preparedStatementsFunction.php';
  require_once 'processing/process.php';

  //get the active games
  $activeGamesArray = ActiveGames($conn);
  //get all future games
  $futureGamesArray = FutureGames($conn);

  //////////////// VIEW FUTURE GAMES /////////////////////////////////
  ?>
  <div class="ViewFutureGames">
    <?php
    ?><div id= "iziModal" class='iziModal'>
        <div class='iziInternal'>
            <img src='img/loader.gif' />
            <b class='endingGame'>DELETING FUTURE GAMES</b>
        </div>
      </div>
    <h3 class="HEADERS">Future Games</h3>
    <?php
    if($futureGamesArray === null){
      ?><h3 class="bMessage">* Error retrieving future games </h3></div><br><?php
    }
    else if($futureGamesArray){
      ?>
      <table>
        <tr class='gameinfosHead'>
        <th>GAME NAME</th>
        <th>START DATE</th>
        <th>END DATE</th>
        <th>GAME TYPE</th>
        <th>EDIT GAME</th>
        <th>DELETE GAME</th>
        </tr>
        <?php
        foreach($futureGamesArray as $g){
          echo "<tr class='gameInfos' id=".$g['Game ID']." type=".$g['ID']." data-selectedPlayers='".$g['SelectedPlayers']."'>
                  <td class='gameName'>".$g['Name']."</td>
                  <td class='startDate'>".$g['StartDate']."</td>
                  <td class='endDate'>".$g['EndDate']."</td>
                  <td id=".$g['ID']." class='nameType'>".$g['NameType']."</td>
                  <td><a class='editFutureGames' href='manageTournament.php?processType=editFutureGame&editGameID=".$g['Game ID']."&gameType=".$g['ID']."&selectedP=".$g['SelectedPlayers']."'>Edit</a></td>
                  <td><a class='deleteFutureGames' href='#loading' rel='modal:open'>Delete</a></td>
                <tr>";
        }
        ?>
      </table>
     </div><br><?php
    }
    else{
      ?><h3> NO FUTURE GAMES</h3>
      </div><br><?php
    }
    //////////////// VIEW ACTIVE GAMES /////////////////////////////
    ?>
    <div class="ViewGames">
    <h3 class="HEADERS">Active Games</h3>
    <?php
    if($activeGamesArray === null){
      ?><h3 class="bMessage">* Error retrieving active games </h3>
      <br>
      <?php
    }
    elseif($activeGamesArray){
      ?>
      <table>
      <tr class='gameinfosHead'>
        <th>GAME NAME</th>
        <th>START DATE</th>
        <th>END DATE</th>
        <th>GAME TYPE</th>
      </tr>
      <tr class="gameinfos">
      <?PHP
      foreach($activeGamesArray as $games){
        //depending on game_type, different view is presented
        echo "<td class='gameInfos'> <a href='displayGame.php?processType=game&gameID=".$games['Game ID']."'>".htmlspecialchars($games['Name'])."</a>
              <td class='gameInfos'>".$games['StartDate']."</td>
              <td class='gameInfos'>".$games['EndDate']."</td>
              <td class='gameInfos'>".$games['NameType']."</td>
            <tr>";
      }
    }
    else{?>
      <h3>NO ACTIVE GAMES</h3><br>
    <?php
    }
    ?>
    </table>
    <br>
    <div class="buttonsDiv">
    <a class="backIndex" href="index.php">Main Menu</a>
    </div><br><br>
    <?php require 'includes/footer.php';?>
    </div>
 </body>
 </html>

