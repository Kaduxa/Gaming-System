$(document).ready(function(){ 

  //initializing iziModal
  var iziModal = document.getElementById('iziModal');
  if(iziModal){
      $("#iziModal").iziModal({
          closeOnEscape: false,
          closeButton: false,
          width: 600,
          zindex: 999,
          overlayClose: false
      });
  }

  //select the round winner and turn the h2 red/bold
  var enableGeneralClick = $('.generalKnockoutView').children('.knockoutview').last().attr('id');

  //hover over only not selected ones
  $("#" + enableGeneralClick + " .matchContainer h2").hover(function() {
    if($(this).attr('id') != 'player0' && $(this).hasClass("")) {
      $(this).css("background-color", "#c4c0c0");
    }
  
    if($(this).siblings("h2").hasClass("selected")) {
      $(this).css("background-color", "");
    }
  }, function() {
  $(this).css("background-color", "");});

  function Select(e){
    //if one is selected the other one is not if player id = o (free player) cannot be select
    if($(this).attr('id') != 'player0') {
      let selected = $(this).hasClass( "selected");
      let siblingHasSelected = $(this).siblings("h2").hasClass("selected");
      //if the button edit is true allow to click on the h2,if false don't
      let selection =  $(this).closest(".matchContainer").find("h2").hasClass("selected");

      //only select one at the time
      $(this).closest(".matchContainer").find("h2").removeClass("selected");
      //before page reload add hover to next h2 to make it selectble 
      $(this).closest(".matchContainer").find("h2").hover(function(){
        if($(this).attr('id') != 'player0'){
            $(this).css("background-color", "#c4c0c0");
        }
      },function() {
        $(this).css("background-color", "");
      });
        
      let finalSelected = $( this ).addClass("selected");

      let playerID = e.target.id;
      winnerID = playerID.substring(6);
      let gameID = $(".ViewGameInfo").attr("id");
      let Match = $(this).parent().attr('id');
      var MatchID = Match.substring(9);
  
      let updateKoWinner = {
      "gameID" : gameID,
      "matchID" : MatchID,
      "winnerID" : winnerID
      }

      $.post('includes/databaseFunctions.php',// url
        {updateKoWinner},  // data to be submit
        function(data, status, jqXHR) 
        {// success callback
            $('.see').append('status: ' + status + ', Winner: ' + data);
        })
        .fail(function(jqxhr, settings, ex)
        { alert('failed, ' + ex); });
        checkNumberOfWinners();
    }
  }

  //if the edit results button is pressed changes the button html vice/versa
  $(".editKoPreviousResult").on("click", function(){
    $("#" + enableGeneralClick + " .matchContainer h2").off("click");
    $("#" + enableGeneralClick + " .matchContainer h2").each(function(){
      var selected = $(this).hasClass("selected");
      var sibSelected = $(this).siblings("h2").hasClass("selected");
      var notSelected = $(this).hasClass("");
      var sibNotSelected = $(this).siblings("h2").hasClass("");
      
      //if editing make the next h2 selectble 
      if(selected == true || sibSelected == true || notSelected == true || sibNotSelected == true ){
        $(".matchContainer h2.selected").siblings("h2").on("click",Select);
        $(".matchContainer h2.selected").on("click",Select);
        //even if there's no winner for that match-up
        $(".matchContainer h2").siblings("h2").on("click",Select);
        $(".matchContainer h2").on("click",Select);
        //allow the hover effect on the selected siblings
        $(".matchContainer h2.selected").siblings("h2").hover(function(){
          if($(this).attr('id') != 'player0'){
            $(this).css("background-color", "#c4c0c0");
          }
        },function() {
          $(this).css("background-color", "");
      });
      }
    });
  
    let html = $(this).html();
    $(this).html($(this).data("prevhtml")).data("prevhtml",html);

    //set global variable to check if button is pressed(edit mode = true) or not(edit mode= false) 
    window.editButtonPressed = (typeof window.editButtonPressed == "undefined" ? true : !window.editButtonPressed);

    if(!window.editButtonPressed){
      //desabled click on the sibling of selected
      $("#" + enableGeneralClick + " .matchContainer h2.selected").siblings("h2").off("click");

      //enter and exit ‘edit mode’ cancel hover on selected
      $("#" + enableGeneralClick + " .matchContainer h2.selected").hover(function(){
        $(this).css("background-color", "");
      },function() {
        $(this).css("background-color", "");
      });

      //if button pressed is turned off,turn off the hover effects
      $("#" + enableGeneralClick + " .matchContainer h2.selected").siblings("h2").hover(function(){
        $(this).css("background-color", "");
      },function() {
        $(this).css("background-color", "");
      });
    }
  });

  $("#" + enableGeneralClick + " .matchContainer h2").on("click", Select );

  $("#" + enableGeneralClick + " .matchContainer h2").each(function(){
    var selected = $(this).hasClass("selected");
    var sibSelected = $(this).siblings("h2").hasClass("selected");
    
    if(selected == true || sibSelected == true){
      $(".matchContainer h2.selected").siblings("h2").off("click");
    }
  });

  function checkNumberOfWinners(){
    // checks winners against match cards
    var numberRounds = $(".knockoutview").length;
    var numberWinner = $('#knockOutRound'+ numberRounds + ' .selected').length;
    // number of matches
    var matchCont = $('#knockOutRound' + numberRounds+ " .matchContainer").length;
    var nextRoundButton = $(".showNextRoundBttn").length;
    var showWinnerButton = $(".showWinnerBttn").length;

    if(numberWinner == 1 && matchCont == 1 && nextRoundButton == 0  && showWinnerButton == 0){
      $('.showWinner').append('<button type="button" class="showWinnerBttn" >CONFIRM WINNER</button>');
    }
    else if(matchCont == 1 && numberWinner == 1 && showWinnerButton == 1){
      $('.showNextRoundBttn').hide();
    }
    else if(nextRoundButton == 0 && matchCont == numberWinner) {
      $('.showNextRound').append('<button type="button" class="showNextRoundBttn" >Next Round Draws</button>');
    }
  }

  //click start next round will generate new round and shuffle and group players
  $(".showNextRound").on("click", function() {
    let gameID = $(".ViewGameInfo").attr("id");
    var numberRounds = $(".knockoutview").length;
    var createNextRound = numberRounds + 1;  //this will check the id of the knockout view, if is 1,them add +1, to create next round

    // find div with selected attribute
    var winnersArray = $('#knockOutRound' + numberRounds + ' .selected')
    .map(function() { return this.id.substring(6); })  // convert to set of IDs
    .get();  // convert to instance of Array 
      
    var enableClick = $(this).siblings('.knockoutview').last().attr('id');
    $( '#' + enableClick +' .matchContainer h2').unbind( "click" );

    let createNewRound = {
      "gameID" : gameID,
      "nextRoundID" : createNextRound,
      "winnersArray" : winnersArray
    }

    $.post('includes/databaseFunctions.php',  // url
      {createNewRound},  // data to be submit
      function(data, status, jqXHR) {// success callback
        $('.see').append('status: ' + status + ', Winner: ' + data);
        var data = JSON.parse(data);
        var dataWinnerArray = data["playerMatch"];

        $('.generalKnockoutView').append("<div class='pulseNextRound'><h2 class='nextRound'>Next Draw Will Be...</h2></div><br><div id='knockOutRound"+ createNextRound +"' class='knockoutview' ></div>");
        $.each( dataWinnerArray, function( key, value ) {
          var p1 = value['P1Name'];
          var p2 = value['P2Name'];
          var mID = value['MatchID'];
          $("#knockOutRound" + createNextRound).append("<div id= 'container" + mID + "' class='nextDrawDiv'><p class='round'>Round: " + createNextRound + "</p><h2 class=''>" + p1 +"</h2><span>VS<span><h2 class=''>"+ p2 +"</h2>");
        });
        $('.showNextRoundBttn').hide();
      })
      .fail(function(jqxhr, settings, ex)
      { alert('failed, ' + ex); });
  });

  //click confirm Winner will get the tournament winner
  $(".showWinner").on("click",function() {
    let gameID = $(".ViewGameInfo").attr("id");
    var numberRounds = $(".knockoutview").length;
    var WinnerID = $('#knockOutRound'+ numberRounds + ' .selected').attr('id');
    var winnerID = WinnerID.substring(6);

    let koWINNER = {
      "gameID" : gameID,
      "winnerID" : winnerID,
      "Round" : numberRounds
    }

    $.post('includes/databaseFunctions.php',  // url
      {koWINNER},  // data to be submit
      function(data, status, jqXHR) 
      {// success callback
        let dataRtn = JSON.parse(data);
        $('.matchContainer').hide();
        $(".editKoPreviousResult").hide();
        $(".warningUser").hide();
        $('.showWinner').hide();
        $("<div class='congrats'>CONGRATULATIONS</div>").insertBefore('.pulseWinner');
        $('.pulseWinner').show();
        $('.pulseWinner').append("<div id="+dataRtn['id']+" class='winnerName'>"+dataRtn['name']+" "+dataRtn['lastname']+"</div>");
        $('.endKoGame').show();
      })
        .fail(function(jqxhr, settings, ex)
        { alert('failed, ' + ex); });
  });

  checkNumberOfWinners();

  //after confirming the winner the button end game is available once pressed sets the active to 0 and redirects to archived games page
  $(".endKoGame").on("click", function(e){
    var koGameId = $(".ViewGameInfo").attr("id");
    var koWinner = $(".winnerName").attr('id');

    var endKoGames = {
      "koGameId": koGameId,
      "koWinner":koWinner
    }

    if(confirm("Are You Sure ???")){
      $('#iziModal').iziModal('open');
      $.post('includes/databaseFunctions.php',// url
      // data to be submit
        {endKoGames},  // data to be submit
        function(data, status, jqXHR) 
        {// success callback
          var objJSON = JSON.parse(data);
            if(objJSON['success'] === 0){
             alert(objJSON['msg']);
            }else{
                location.href = "archivedGames.php?processType=Ended";
            }
        })
          .fail(function(jqxhr, settings, ex)
          { alert('failed, ' + ex); });
    }
    else{
      e.preventDefault();
    }
  });

  //if there's winner
  $(".endKoGame1").on("click", function(e) {
  var koGameId = $(".ViewGameInfo").attr("id");
  var koWinner = $(".ifWiner").attr("id");

  var endKoGames = {
    "koGameId": koGameId,
    "koWinner":koWinner
  }

  if(confirm("Are You Sure ???")){
    $('#iziModal').iziModal('open');
      $.post('includes/databaseFunctions.php',// url
      // data to be submit
      {endKoGames},  // data to be submit
      function(data, status, jqXHR) 
      {// success callback
        var objJSON = JSON.parse(data);
        if(objJSON['success'] === 0){
          alert(objJSON['msg']);
        }else{
          location.href = "archivedGames.php?processType=Ended";
        }
      })
        .fail(function(jqxhr, settings, ex)
        { alert('failed, ' + ex); });
  }
  else{
    e.preventDefault();
  }
  });

  //check if the url contains Archived string, if yes change the button text and href
  if(window.location.href.indexOf("Archived") > -1){
    var backIndex = document.getElementById('knockoutBackBttn');
    if(backIndex){
      backIndex.setAttribute('href','archivedGames.php');
      backIndex.setAttribute('class','button2');
      backIndex.innerHTML="Back";
    }
  }

});