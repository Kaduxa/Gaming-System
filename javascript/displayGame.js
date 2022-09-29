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

  //initializing iziModal
  var iziModalInsertTempScore = document.getElementById('iziModalInsertTempScore');
  if(iziModalInsertTempScore){
    $("#iziModalInsertTempScore").iziModal({
        closeOnEscape: false,
        closeButton: false,
        width: 600,
        zindex: 999,
        overlayClose: false
    });
  }

    /*
    When the end game button(totalScore page) is pressed
    shows the confirm box before
    end game and redirect to archived games page
  */
  $(".endAllVsAllGame").on("click", function(e){
    var gameId = $(".ViewGameInfo").attr("id");
    var topScorer = $(".leaderBoard").siblings("tr:first").children().attr("id");

    var endAllVsAllGames = {
      "gameId": gameId,
      "topScorer":topScorer
    }

    if(confirm("Are You Sure???")){
      $('#iziModal').iziModal('open');
      $.post('includes/databaseFunctions.php',// url
      // data to be submit
      {endAllVsAllGames},  // data to be submit
      function(data, status, jqXHR) 
      {// success callback
        var objJSON = JSON.parse(data);
        if(objJSON['success'] === 0){
          alert(objJSON['msg']);
        }
        else{
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

  //postioning the scrollbar horizontally in the last position of the div width
  var scrollPosition = document.getElementById('playerPoints');
  if(scrollPosition){
    var position = $("#playerPoints")[0].scrollWidth;
    document.getElementById('playerPoints').scrollLeft += position;
  }

  //check if the url contains Archived string, if yes change the button text and href
  if(window.location.href.indexOf("Archived") > -1){
    var backIndex = document.getElementById('allVsAllBackBttn');
    if(backIndex){
      backIndex.setAttribute('href','archivedGames.php');
    }
  }

  //add existing temp score that was not put live
  $(".addExistingTempScore").on("click",function(e){
      var gameID = $('.ViewGameInfo').attr('id');

      var tempScoresInfo = {
        "gameID" : gameID
      }

      if(confirm("You about to put temporary scores live !!")){
        $('#iziModalInsertTempScore').iziModal('open');
        $.post('includes/databaseFunctions.php',// url
          // data to be submit
          {tempScoresInfo},  // data to be submit
          function(data, status, jqXHR)
          {// success callback
            $('#iziModalInsertTempScore').iziModal('close');
            location.href = "displayGame.php?processType=game&gameID="+gameID+"&Inserted";
          }
        )
        .fail(function(jqxhr, settings, ex)
        { alert('failed, ' + ex); });
      }
      else{
        e.preventDefault();
      }
  });
  
});