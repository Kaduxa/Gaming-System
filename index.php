<?php
  require_once 'includes/header.php';
  /*
   * @author: Ricardo Lopes
   */

  //after game is created show good message
  if(isset($_GET['created'])) {
    echo "<div class='GoodMessage'><p>GAME CREATED SUCESSFULLY</p></div>";
  }
  if(isset($_GET['edited']) ) {
    echo "<div class='GoodMessage'><p>GAME EDITED SUCESSFULLY</p></div>";
  }
  ?>
  <div class="inicialDiv">
    <div class="logo">
      <img class="initialLogo" src="img/portal.png" alt="Gaming System Portal" title="Gaming System Portal">
    </div>
      <br>
        <a class="button" href="manageTournament.php?processType=createTournament">Start New Tournament</a>
        <a class="button" href="viewGames.php">Check Tournament Progress</a>
        <a class="button" href="archivedGames.php">See All Archived Tournaments</a><br>
        <?php require 'includes/footer.php';?>
   </div>
  </body>
  </html>
