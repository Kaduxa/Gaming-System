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

  //get tournament rounds once the round number are clicked
  const rounds = document.querySelectorAll(".rounds");
  for (const round of rounds){
    round.addEventListener('click', function(event) {
      let roundId = round.id;
      GetRound(roundId);
    })
  }

  //end tournament game
  $(".endTournGame").on("click", function(e){
    var tGameId = $(".ViewGameInfo").attr("id");
    var tTopScorer = $(".leaderBoard").siblings("tr:first").children().attr("id");

    var endTournamentGames = {
        "tGameId": tGameId,
        "tTopScorer":tTopScorer
    }

    if(confirm("Are You Sure ???")){
      $('#iziModal').iziModal('open');
      $.post('includes/databaseFunctions.php',// url
      // data to be submit
      {endTournamentGames},  // data to be submit
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

  //tournament submit score button
  $(".enterTournPoints").on("click",function(e) {
    e.preventDefault();
    $(document).keydown(function(e){
      if(e.keyCode == 13) {
        e.preventDefault();
        return false;
      }
    });

    var homeScore = $(this).siblings('.homeTeam').children('.tournScoreHome').val();
    var awayScore = $(this).siblings('.awayTeam').children('.tournScoreAway').val();
    let gameID = $('.ViewGameInfo').attr('id');
    var player1ID = $(this).siblings('.homeTeam').children('.tournScoreHome').attr('id');
    var player2ID = $(this).siblings('.awayTeam').children('.tournScoreAway').attr('id');
    var round = $(this).parent('.game').attr('round');
    var matchID =  $(this).parent().attr('id');

    //checks if the inputs are not empty
    if(homeScore === "" || awayScore === ""){
      $('.badMessageEmptyScore').show(); 
      $('.badMessageEmptyScore').html('MAKE SURE THE INPUT IS NOT EMPTY');
    }
      //check if score is a valid number
    else if($.isNumeric(homeScore) == false || $.isNumeric(awayScore) == false){
      $('.badMessageEmptyScore').show(); 
      $('.badMessageEmptyScore').html('MAKE SURE THE SCORE ARE NUMBER');
    }
    //check if score is a valid integer
    else if(homeScore % 1 !== 0 || awayScore % 1 !== 0){
      $('.badMessageEmptyScore').show(); 
      $('.badMessageEmptyScore').html('MAKE SURE THE SCORES ARE VALID INTEGER');
    }
    else if(homeScore < 0){
      $('.badMessageEmptyScore').show(); 
      $('.badMessageEmptyScore').html('MAKE SURE THE SCORE IS BIGGER THAN 0');
    }
    else{
      var winner = 'No winner';
      var win = 3 ;
      var lost = 0;
      var draw = 1;
      var p1Points;
      var p2Points;

      var homeInput = $(this).siblings('.homeTeam').children('.tournScoreHome');
      var awayInput = $(this).siblings('.awayTeam').children('.tournScoreAway');
      var vs =  $(this).siblings('.vs');
      var results =  $(this).siblings('.results');
      var submitButton = $(this);
      var editButton = $(this).siblings('.editTourPoint');

      if(homeScore > awayScore){
        winner = player1ID;
        p1Points = win;
        p2Points = lost;
      }
      else if(awayScore > homeScore){
        winner = player2ID;
        p2Points = win;
        p1Points = lost;
      }
      else{
        p1Points = draw;
        p2Points = draw;
      }

      let SubmitResults = {
        "gameID": gameID,
        "homeScore": homeScore,
        "awayScore": awayScore,
        "player1ID": player1ID,
        "player2ID": player2ID,
        "player1Points": p1Points,
        "player2Points": p2Points,
        "matchID": matchID,
        "round": round,
        "winnerID": winner
      }

      $.post('includes/databaseFunctions.php',// url
        {SubmitResults},  // data to be submit
        function(data, status, jqXHR) 
        {// success callback
          var data = JSON.parse(data);
          var datahomeScore = data["homeScore"];
          var dataAwayScore = data["awayScore"];

          homeInput.hide();
          awayInput.hide();
          submitButton.hide();
          editButton.show();
          vs.hide();
          results.show();
          results.html("<p class='homeResults'>" + datahomeScore + "</p> : <p class='awayResults'>" + dataAwayScore +"</p>");

        })
        .fail(function(jqxhr, settings, ex)
        { alert('failed, ' + ex); });
    }
  });

  //once the edit tournament button is clicked 
  $(".editTourPoint").on("click",function(e) {
    e.preventDefault();
    var editButton = $(this);
    var submit = $(this).siblings('.enterTournPoints');
    var results = editButton.siblings(".results");
    var vs = editButton.siblings(".vs");
    var homeInput = $(this).siblings(".homeTeam").children(".tournScoreHome");
    var homeInputValue = $(this).siblings(".results").children(".homeResults").html();
    var awayInput = $(this).siblings(".awayTeam").children(".tournScoreAway");
    var awayInputValue = $(this).siblings(".results").children(".awayResults").html();
    var updateButton = $(this).siblings('.updateTourPoint');

    editButton.hide();
    results.hide();
    homeInput.show();
    awayInput.show();
    vs.show();
    homeInput.val(homeInputValue);
    awayInput.val(awayInputValue);
    updateButton.show();
  });

  //update the tournament points
  $(".updateTourPoint").on("click", function(e){
    e.preventDefault();
    $(document).keydown(function(e){
      if(e.keyCode == 13) {
        e.preventDefault();
        return false;
      }
    });

    var homeScore = $(this).siblings('.homeTeam').children('.tournScoreHome').val();
    var awayScore = $(this).siblings('.awayTeam').children('.tournScoreAway').val();
    let gameID = $('.ViewGameInfo').attr('id');
    var player1ID = $(this).siblings('.homeTeam').children('.tournScoreHome').attr('id');
    var player2ID = $(this).siblings('.awayTeam').children('.tournScoreAway').attr('id');
    var round = $(this).parent('.game').attr('round');
    var matchID =  $(this).parent().attr('id');

    if(homeScore == "" || awayScore == ""){
      $('.badMessageEmptyScore').show(); 
      $('.badMessageEmptyScore').html('Error');
    }
      
    var winner = 'No winner';
    var win = 3 ;
    var lost = 0;
    var draw = 1;
    var p1Points;
    var p2Points;

    var homeInput = $(this).siblings('.homeTeam').children('.tournScoreHome');
    var awayInput = $(this).siblings('.awayTeam').children('.tournScoreAway');
    var vs =  $(this).siblings('.vs');
    var results =  $(this).siblings('.results');
    var updateButton = $(this);
    var editButton = $(this).siblings('.editTourPoint');

    if(homeScore > awayScore){
      winner = player1ID;
      p1Points = win;
      p2Points = lost;
    }
    else if(awayScore > homeScore){
        winner = player2ID;
        p2Points = win;
        p1Points = lost;
    }
    else{
      p1Points = draw;
      p2Points = draw;
    }

    let updateResults={
      "gameID": gameID,
      "homeScore": homeScore,
      "awayScore": awayScore,
      "player1ID": player1ID,
      "player2ID": player2ID,
      "player1Points": p1Points,
      "player2Points": p2Points,
      "matchID": matchID,
      "round": round,
      "winnerID": winner
    }

    $.post('includes/databaseFunctions.php',// url
      {updateResults},  // data to be submit
      function(data, status, jqXHR) {// success callback
        var data = JSON.parse(data);
        var datahomeScore = data["homeScore"];
        var dataAwayScore = data["awayScore"];

        homeInput.hide();
        awayInput.hide();
        updateButton.hide();
        editButton.show();
        vs.hide();
        results.show();
        results.html("<p class='homeResults'>"+datahomeScore + "</p> : <p class='awayResults'>" + dataAwayScore+"</p>");
      }
    )
    .fail(function(jqxhr, settings, ex)
    { alert('failed, ' + ex); });
  });

  //check if the url contains Archived string, if yes change the button text and href
  if(window.location.href.indexOf("Archived") > -1){
    var backIndex = document.getElementById('tournamentBackBttn');
    if(backIndex){
      backIndex.setAttribute('href','archivedGames.php');
      backIndex.innerHTML="Back";
    }
  }
  
  //get tournament round function
  function GetRound(e){
    let round = e;
    let tournamentID = $('.ViewGameInfo').attr('id');

    let getRounds ={
        "round": round,
        "tournamentID": tournamentID
    }
  
    $.post('includes/databaseFunctions.php',// url
      {getRounds},  // data to be submit
      function(data, status, jqXHR) {// success callback
        var data = JSON.parse(data);
        $('.jornada').html("ROUND : "+ round);
        $('.game').hide() ;

        $.each(data, function( index, value ){
          var matchID = value['MatchID'];
          var player1ID = value['Player1ID'];
          var player2ID = value['Player2ID'];
          var player1Name = value['P1Name'];
          var player2Name = value['P2Name'];
          var round = value['Round'];
          var ResultP1 = value['ResultP1'];
          var ResultP2 = value['ResultP2'];

          let returnResponse = "<div class ='game' id='"+ matchID +"' round='" + round + "'>";
              returnResponse +=   "<div class='homeTeam'>";
              returnResponse +=       "<p class='pName'>" + player1Name + "</p>";
              returnResponse +=       "<input type='text' maxlength='2' id='" + player1ID + "' class='tournScoreHome'/>";
              returnResponse +=   "</div>";
              returnResponse +=   "<div class='results' style='display:none'><p class='homeResults'>" + ResultP1 + "</p> : <p class='awayResults'>" + ResultP2 + "</div>";
              returnResponse +=   "<div class='vs'> vs </div>";
              returnResponse +=   "<div class='awayTeam'>";
              returnResponse +=       "<input type='text' id='" + player2ID + "' maxlength='2' class='tournScoreAway'/>";
              returnResponse +=       "<p class='pName'>" + player2Name + "</p>";
              returnResponse +=   "</div>";
              returnResponse +=   "<button type='button' class='enterTournPoints'>Submit</button>";
              returnResponse +=   "<button type='button' class='editTourPoint' style='display:none'>Edit</button>";
              returnResponse +=   "<button type='button' class='updateTourPoint' style='display:none'>Update</button>";
                returnResponse +=   "</div>";

          let response1 = "<div class ='game' id='" + matchID + "' round='" + round + "'>";
                  response1 +=    "<div class='homeTeam'>";
                  response1 +=         "<p class='pName'>" + player1Name + "</p>";
                  response1 +=         "<input type='text' maxlength='2' id='" + player1ID + "' class='tournScoreHome' style ='display:none' />";
                  response1 +=    "</div>";
                  response1 +=    "<div class ='results'><p class='homeResults'>" + ResultP1 + "</p> :  <p class='awayResults'>" + ResultP2 + "</p></div>";
                  response1 +=    "<div class ='vs' style ='display:none'> vs </div>";
                  response1 +=    "<div class='awayTeam'>";
                  response1 +=    "<input type='text' id='" + player2ID + "' maxlength='2' class='tournScoreAway' style ='display:none'/>";
                  response1 +=    "<p class='pName'>" + player2Name + "</p>";
                  response1 +=    "</div>";
                  response1 +=    "<button type='button' class='enterTournPoints' style='display:none'>Submit</button>";
                  response1 +=    "<button type='button' class='editTourPoint'>Edit</button>";
                  response1 +=    "<button type='button' class='updateTourPoint' style='display:none'>Update</button>";
                  response1 +=    "</div>";

          let response2 =  "<div class ='game' id='" + matchID + "' round='" + round + "'> ";
                  response2 +=    "<div class='homeTeam'>";
                  response2 +=        "<p class='freeRound'>" + player1Name + "</p>";
                  response2 +=    "</div> ";
                  response2 +=    "<div class='awayTeam'>";
                  response2 +=        " <p class='freeRound'>" + player2Name + "</p>";
                  response2 +=    "</div>";
                  response2 +=   "</div>";

          if(player1Name === "FREE THIS ROUND" || player2Name === "FREE THIS ROUND"){
            $('.matches').append(response2);
          }    
          else if(ResultP1 === null && ResultP2 === null){
            $('.matches').append(returnResponse);
          }
          else{
            $('.matches').append(response1);
          }
        });

        ///////////////////////////////////////////////////////////////
        ///////////////////////////////////////////////////////////////
        
        $(".enterTournPoints").click(function(e) {
          e.preventDefault();
          var homeScore = $(this).siblings('.homeTeam').children('.tournScoreHome').val();
          var awayScore = $(this).siblings('.awayTeam').children('.tournScoreAway').val();
          let gameID = $('.ViewGameInfo').attr('id');
          var player1ID = $(this).siblings('.homeTeam').children('.tournScoreHome').attr('id');
          var player2ID = $(this).siblings('.awayTeam').children('.tournScoreAway').attr('id');
          var round = $(this).parent('.game').attr('round');
          var matchID =  $(this).parent().attr('id');

          if(homeScore == "" || awayScore == ""){
            return alert('Make Sure the input is not empty');
          }
            
          var winner = 'No winner';
          var win = 3 ;
          var lost = 0;
          var draw = 1;
          var p1Points;
          var p2Points;
          var homeInput = $(this).siblings('.homeTeam').children('.tournScoreHome');
          var awayInput = $(this).siblings('.awayTeam').children('.tournScoreAway');
          var vs =  $(this).siblings('.vs');
          var results =  $(this).siblings('.results');
          var submitButton = $(this);
          var editButton = $(this).siblings('.editTourPoint');

          if(homeScore > awayScore){
            winner = player1ID;
            p1Points = win;
            p2Points = lost;
          }
          else if(awayScore > homeScore){
            winner = player2ID;
            p2Points = win;
            p1Points = lost;
          }
          else{
            p1Points = draw;
            p2Points = draw;
          }

          let SubmitResults={
            "gameID": gameID,
            "homeScore": homeScore,
            "awayScore": awayScore,
            "player1ID": player1ID,
            "player2ID": player2ID,
            "player1Points": p1Points,
            "player2Points": p2Points,
            "matchID": matchID,
            "round": round,
            "winnerID": winner
          }

          $.post('includes/databaseFunctions.php',// url
          {SubmitResults},  // data to be submit
            function(data, status, jqXHR){// success callback
            var data = JSON.parse(data);
            var datahomeScore = data["homeScore"];
            var dataAwayScore = data["awayScore"];

            homeInput.hide();
            awayInput.hide();
            submitButton.hide();
            editButton.show();
            vs.hide();
            results.show();
            results.html("<p class='homeResults'>" + datahomeScore + "</p> : <p class='awayResults'>" + dataAwayScore + "</p>");
            })
          .fail(function(jqxhr, settings, ex)
          { alert('failed, ' + ex); });
        });

        ///////////////////////////////////////////////////////////////
        ///////////////////////////////////////////////////////////////

        $(".editTourPoint").click(function(e) {
          e.preventDefault();
          var editButton = $(this);
          var update = $(this).siblings('.updateTourPoint');
          var results = editButton.siblings(".results");
          var vs = editButton.siblings(".vs");
          var homeInput = $(this).siblings(".homeTeam").children(".tournScoreHome");
          var homeInputValue = $(this).siblings(".results").children(".homeResults").html();
          var awayInput = $(this).siblings(".awayTeam").children(".tournScoreAway");
          var awayInputValue = $(this).siblings(".results").children(".awayResults").html();

          editButton.hide();
          results.hide();
          update.show();
          homeInput.show();
          awayInput.show();
          vs.show();
          homeInput.val(homeInputValue);
          awayInput.val(awayInputValue);
        });

        ///////////////////////////////////////////////////////////////
        ///////////////////////////////////////////////////////////////

        $(".updateTourPoint").click(function(e){
          e.preventDefault();
          var homeScore = $(this).siblings('.homeTeam').children('.tournScoreHome').val();
          var awayScore = $(this).siblings('.awayTeam').children('.tournScoreAway').val();
          let gameID = $('.ViewGameInfo').attr('id');
          var player1ID = $(this).siblings('.homeTeam').children('.tournScoreHome').attr('id');
          var player2ID = $(this).siblings('.awayTeam').children('.tournScoreAway').attr('id');
          var round = $(this).parent('.game').attr('round');
          var matchID =  $(this).parent().attr('id');

          if(homeScore == "" || awayScore == ""){
            return alert('Make Sure the input is not empty');
          }
            
          var winner = 'No winner';
          var win = 3 ;
          var lost = 0;
          var draw = 1;
          var p1Points;
          var p2Points;

          var homeInput = $(this).siblings('.homeTeam').children('.tournScoreHome');
          var awayInput = $(this).siblings('.awayTeam').children('.tournScoreAway');
          var vs =  $(this).siblings('.vs');
          var results =  $(this).siblings('.results');
          var updateButton = $(this);
          var editButton = $(this).siblings('.editTourPoint');

          if(homeScore > awayScore){
            winner = player1ID;
            p1Points = win;
            p2Points = lost;
          }
          else if(awayScore > homeScore){
            winner = player2ID;
            p2Points = win;
            p1Points = lost;
          }
          else{
            p1Points = draw;
            p2Points = draw;
          }

          let updateResults={
            "gameID": gameID,
            "homeScore": homeScore,
            "awayScore": awayScore,
            "player1ID": player1ID,
            "player2ID": player2ID,
            "player1Points": p1Points,
            "player2Points": p2Points,
            "matchID": matchID,
            "round": round,
            "winnerID": winner
          }

          $.post('includes/databaseFunctions.php',// url
          {updateResults},  // data to be submit
            function(data, status, jqXHR) {// success callback
              var data = JSON.parse(data);
              var datahomeScore = data["homeScore"];
              var dataAwayScore = data["awayScore"];

              homeInput.hide();
              awayInput.hide();
              updateButton.hide();
              editButton.show();
              vs.hide();
              results.show();
              results.html("<p class='homeResults'>" + datahomeScore + "</p> : <p class='awayResults'>" + dataAwayScore + "</p>");
            })
          .fail(function(jqxhr, settings, ex)
          { alert('failed, ' + ex); });
        });
      }
    )
  };

  /////////////////////// Tournament History  /////////////////////////

  //get history tournament rounds once the round number are clicked
  const historyRounds = document.querySelectorAll(".historyRounds");
  for (const rounds of historyRounds) {
    rounds.addEventListener('click', function(event) {
      let historyRoundId = rounds.id;
      GetHistoryRound(historyRoundId);
    })
  }

  //get tournament history round function
  function GetHistoryRound(e){
    let round = e;
    let tournamentID = $('.ViewGameInfo').attr('id');

    let getRounds ={
      "round": round,
      "tournamentID": tournamentID
    }

    $.post('includes/databaseFunctions.php',// url
      {getRounds},  // data to be submit
      function(data, status, jqXHR) {// success callback
        var data = JSON.parse(data);
        $('.jornada').html("ROUND : "+ round);
        $('.game').hide();

        $.each(data, function( index, value ){
          var matchID = value['MatchID'];
          var player1ID = value['Player1ID'];
          var player2ID = value['Player2ID'];
          var player1Name = value['P1Name'];
          var player2Name = value['P2Name'];
          var round = value['Round'];
          var ResultP1 = value['ResultP1'];
          var ResultP2 = value['ResultP2'];

          //if there's no scores
          let returnResponse = "<div class ='game' id='"+ matchID +"' round='" + round + "'>";
              returnResponse +=   "<div class='homeTeam'>";
              returnResponse +=       "<p class='pName'>" + player1Name + "</p>";
              returnResponse +=   "</div>";
              returnResponse +=   "<div class='results' style='display:none'><p class='homeResults'>" + ResultP1 + "</p> : <p class='awayResults'>" + ResultP2 + "</div>";
              returnResponse +=   "<div class='vs'> vs </div>";
              returnResponse +=   "<div class='awayTeam'>";
              returnResponse +=       "<p class='pName'>" + player2Name + "</p>";
              returnResponse +=   "</div>";
              returnResponse +=   "</div>";

          //if there's scores
          let response1 = "<div class ='game' id='" + matchID + "' round='" + round + "'>";
                response1 +=    "<div class='homeTeam'>";
                response1 +=         "<p class='pName'>" + player1Name + "</p>";
                response1 +=    "</div>";
                response1 +=    "<div class ='results'><p class='homeResults'>" + ResultP1 + "</p> :  <p class='awayResults'>" + ResultP2 + "</p></div>";
                response1 +=    "<div class ='vs' style ='display:none'> vs </div>";
                response1 +=    "<div class='awayTeam'>";
                response1 +=    "<p class='pName'>" + player2Name + "</p>";
                response1 +=    "</div>";
                response1 +=    "</div>";

          let response2 =  "<div class ='game' id='" + matchID + "' round='" + round + "'> ";
                response2 +=    "<div class='homeTeam'>";
                response2 +=        "<p class='freeRound'>" + player1Name + "</p>";
                response2 +=    "</div> ";
                response2 +=    "<div class='awayTeam'>";
                response2 +=        " <p class='freeRound'>" + player2Name + "</p>";
                response2 +=    "</div>";
                response2 +=   "</div>";

          if(player1Name === "FREE THIS ROUND" || player2Name === "FREE THIS ROUND"){
            $('.matches').append(response2);
          }    
          else if(ResultP1 === null && ResultP2 === null){
            $('.matches').append(returnResponse);
          }
          else{
            $('.matches').append(response1);
          }
        });
      }
    )
  };
 
});