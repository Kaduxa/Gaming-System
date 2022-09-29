<?php
  /*
      Description of Fixture
      This class implements the creation of a role play according to the number
      of teams received to be instantiated.
      It takes an array with a list of teams and returns another array list of 
      games to be played by the teams on each round. (e.g. aux Array)
  */
  class Fixture{
    //array list of games to be played
    private $aux = array();
    //Array to pairs rounds
    private $pair = array();
    //Array to odds rounds
    private $odd = array();
    //Counter or number of games
    private $countGames = 0;
    //Counter of number of teams
    private $countTeams = 0;
    
    //$teams Array with the teams ids
    public function __construct(array $teams) {
      if(is_array($teams)){
        //shuffles the order of the ids
        shuffle($teams);
        //checks if the total players passed is equal to total of teams 
        $this->countTeams = count($teams);
        if($this->countTeams % 2 == 1){
          $this->countTeams++;
          //if there's total of players are odd
          $teams[] = "free this round";
        }
        //returns number of rounds depending on number of players
        $this->countGames = floor($this->countTeams/2);
        for($i = 0; $i < $this->countTeams; $i++){
          $this->aux[] = $teams[$i];
        }
      }
      else{
        return false;
      }
    }
    
    // It make the starting round return Array AuxArray with the matches of the round one or pair round
    private function Init(){
        for($x = 0; $x < $this->countGames; $x++){
            $this->pair[$x][0] = $this->aux[$x];
            $this->pair[$x][1] = $this->aux[($this->countTeams - 1) - $x];
        }
        return $this->pair;
    }

    //Returns the schedule generated returns Array with the full matches created
    public function GetSchedule(){
      $rol = array();
      $rol[] = $this->Init();
      for($y = 1; $y < $this->countTeams-1; $y++){
        if($y % 2 == 0){
            $rol[] = $this->GetPairRound();
        }
        else{
            $rol[] = $this->GetOddRound();
        }
      }
      return $rol;
    }
    
    //Create the matches of a pair roundArray with the matches created
    private function GetPairRound(){
      for($z = 0; $z < $this->countGames; $z++){
        if($z == 0){
            $this->pair[$z][0] = $this->odd[$z][0];
            $this->pair[$z][1] = $this->odd[$z + 1][0];
        }
        elseif($z == $this->countGames-1){
            $this->pair[$z][0] = $this->odd[0][1];
            $this->pair[$z][1] = $this->odd[$z][1];
        }
        else{
            $this->pair[$z][0] = $this->odd[$z][1];
            $this->pair[$z][1] = $this->odd[$z + 1][0];
        }
      }
      return $this->pair;
    }
    
    //Create the matches of a odd round return Array with the matches created
    private function GetOddRound(){
      for($j = 0; $j < $this->countGames; $j++){
        if($j == 0){
          $this->odd[$j][0] = $this->pair[$j][1];
          $this->odd[$j][1] = $this->pair[$this->countGames - 1][0]; 
        }
        else{
          $this->odd[$j][0] = $this->pair[$j][1];
          $this->odd[$j][1] = $this->pair[$j - 1][0];
        }
      }
      return $this->odd;
    }
  }

