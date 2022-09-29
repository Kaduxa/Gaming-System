<?php
  require_once 'includes/dbConnnection.php';
  require_once 'includes/header.php';
  require_once 'classes/allClasses.php';
  require_once 'includes/preparedStatementsFunction.php';
  require_once 'processing/process.php';
  
  //get all the tournament winners(archived games)
  $competitionWinners = GetCompetitionWinners($conn);
  ?>
  <div class="ViewArchivedGames">
  <h3 class="HEADERS">ARCHIVED GAMES</h3><br>
  <?php
  if($competitionWinners === null){
    ?><h3>* ERROR LOADING ARCHIVED GAMES</h3><?php
  }
  else if(!$competitionWinners){
    ?><h3>* NO ARCHIVED GAMES</h3><?php
  }
  else{
    ?>
    <table>
    <tr class='gameinfosHead'>
    <th>GAME NAME</th>
    <th>START DATE</th>
    <th>END DATE</th>
    <th>GAME TYPE</th>
    <th>WINNER</th>
    <th>GAME HISTORY</th>
    </tr>
    <tr>
    <?php
    foreach($competitionWinners as $games=>$winnerInfo){
      echo "<tr class='gameInfos' id=".$winnerInfo['ID']." >
              <td><b>".$winnerInfo['Game Name']."</b></td>
              <td>". $winnerInfo['StartDate']."</td>
              <td>".$winnerInfo['End Date']."</td>
              <td>".$winnerInfo['NameType']."</td>
              <td><b>".$winnerInfo['Winner']."<img class='winner' src='img/win.png' /></b></td>
              <td><a href='displayGame.php?processType=game&gameID=".$winnerInfo['ID']."&Archived'>History</a></td>
            </tr>";
    }
    ?>
    </tr></table><?php 
  }?>
  <br><div class="buttonsDiv">
    <a class="backIndex" href="index.php">Main Menu</a>
    </div><br><br>
    <?php require 'includes/footer.php'; ?>
    </div>
    </body>
  </html>

