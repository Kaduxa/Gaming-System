<?php
  require_once 'includes/dbConnnection.php';
  require_once 'includes/header.php';
  require_once 'classes/allClasses.php';
  require_once 'includes/preparedStatementsFunction.php';
  require_once 'processing/process.php';

  try{
    $gameInfo = new $classInfoName($conn,$gameID);
  }catch(Exception $e){
    echo "<div class='ViewGameInfo'><h3>* ERROR RETRIEVING GAME INFO</h3></div>";
  }
  
  $gameInfo->GetInfoBox();
  
  if(method_exists($gameInfo,'LeaderBoard')){
   $gameInfo->LeaderBoard();
  }
 
  $gameInfo->MainBoard();

