<?php
  require_once 'includes/dbConnnection.php';
  require_once 'includes/header.php';
  require_once 'classes/allClasses.php';
  require_once 'includes/preparedStatementsFunction.php';
  require_once 'processing/process.php';

  $gameInfo = new $classInfoName($conn,$gameID);
  $gameInfoBox = $gameInfo->GetInfoBox();
  $enterTodayScoreBoard = $gameInfo->EnterScoreBoard();
