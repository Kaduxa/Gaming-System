$(document).ready(function(){ 

  $(".scoreAttempt").on("keyup", function(event) {
    if ($(this).is(":focus")  && event.keyCode === 13) {
      event.preventDefault();

      if($(this).parent().siblings().children(".addSinglePoints").is(':visible')){
        $(this).parent().siblings().children(".addSinglePoints").click();
      }
      if( $(this).parent().siblings().children(".updateSinglePoints").is(':visible')){
        $(this).parent().siblings().children(".updateSinglePoints").click();
      }
      if($(this).parent().siblings().children(".addSinglePointsDay").is(':visible')){
        $(this).parent().siblings().children(".addSinglePointsDay").click();
      }
      if( $(this).parent().siblings().children(".updateSinglePointsDay").is(':visible')){
        $(this).parent().siblings().children(".updateSinglePointsDay").click();
      }
    }
  });

  //initializing iziModal adding all scores
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

  //initializing iziModal adding single score
  var iziModalScore = document.getElementById('iziModalScore');
  if(iziModalScore){
    $("#iziModalScore").iziModal({
      closeOnEscape: false,
      closeButton: false,
      width: 600,
      zindex: 999,
      overlayClose: false
    });
  }

  //initializing iziModal updating single score
  var iziModalScore = document.getElementById('iziModalUpdateScore');
  if(iziModalScore){
    $("#iziModalUpdateScore").iziModal({
      closeOnEscape: false,
      closeButton: false,
      width: 600,
      zindex: 999,
      overlayClose: false
    });
  }

  //button back pressed (enter day score game view)
  $('#backBtnGameView').on("click", function(e){ 
    if(confirm("THE SCORE'S WON'T BE LIVE!!!")){
    }
    else{
      e.preventDefault();
    }
  });

  //button back  pressed (editGameView)
  var backTotalScore = document.getElementById('backTotalScore');
  if(backTotalScore){
    backTotalScore.addEventListener("click", function(e){
      //check length of unsaved scores
      if( $('.scoreAttempt:visible').length > 0){
        if (confirm("There's Unsaved Scores. Are you sure??")){
        }
        else{
          e.preventDefault();
        }
      }
    });
  }
   
  //when update all Points button is pressed to enter all the scores
  var updateAllPoints = document.getElementById('updateAllPoints');
  if(updateAllPoints){
   updateAllPoints.addEventListener("click", function(e){
    let gameID = $('.ViewGameInfo').attr('id');
    var minValue =  parseInt($('.scoreAttempt').attr('min'));
    var maxValue =  parseInt($('.scoreAttempt').attr('max'));
    let date = $("#dateScore").val();
    let scoreDate = $(".pointsInput").data("date");
    var startDate = $('.startDate').html();
    var endDate = $('.endDate').html();
    var attempts = [];
    var errorArray = [];
    var dateError = $('.badMessageEmptyScore');

    //check if date is empty
    if(date == ''){
      dateError.html('MAKE SURE DATE IS SELECTED').show();
      $(window).scrollTop($('.badMessageEmptyScore:first').position().top);
      errorArray.push("error"); 
      return false;
    }

    // Checks for the following valid date formats:
    //YYYY/MM/DD
    var datePat = /^\d{4}-\d{2}-\d{2}$/;
    var matchArray = date.match(datePat); // is the format ok?
    if (matchArray == null) {
      dateError.html('MAKE SURE DATE IS IN YYYY-MM-DD FORMAT').show();
      $(window).scrollTop($('.badMessageEmptyScore:first').position().top);
      errorArray.push("error"); 
      return false;
    }

    //check if the selected date is less than start date
    if(date < startDate){
      dateError.html('MAKE SURE DATE IS EQUAL OR BIGGER THAN START DATE').show();
      $(window).scrollTop($('.badMessageEmptyScore:first').position().top);
      errorArray.push("error"); 
      return false;
    }

     //check if the selected date is bigger than end date
    if(date > endDate){
      dateError.html('MAKE SURE DATE IS NOT BIGGER THAN END DATE').show();
      $(window).scrollTop($('.badMessageEmptyScore:first').position().top);
      errorArray.push("error"); 
      return false;
    }

    //check if date selected is different from the data saved when the scores were entered
    if(scoreDate != ''){
      if(date != scoreDate){
        if(confirm('You about to change the date the scores are entered..')){
        }else{
          e.preventDefault();
        }
      }
    }


    $('.scoreAttempt').each(function(index, element){
      var plID = $(this).siblings('.showDailyScore').attr("id");

      if(typeof attempts[plID] === 'undefined'){
        attempts[plID] = [];
      }
      
      var scoreVal = $(this).val();
      var errorMessage = $(this).parent().parent().siblings('.errorMsg');
      var scoreLength = scoreVal.length;
      var maxValueLength = maxValue.toString().length;
      var checkVal =  element.checkValidity();


      if(scoreVal % 1 != 0){
        //alert("MAKE SURE YOU ENTER A VALID INTEGER !!");
        errorMessage.html('<i>Make Sure the value is a valid integer</i>').show();
        $(window).scrollTop($('.errorMsg:first').position().top);
        errorArray.push("error"); 
        return false;
      }
      else if(scoreVal!= '' && scoreVal < minValue || scoreVal!= '' && scoreVal > maxValue){
        //alert("MAKE SURE VALUE IS BETWEEN " + minValue +" & "+ maxValue+" !!");
        errorMessage.html('<i>Make Sure Value is between <br> <b>' + minValue + '</b> and <b>'+ maxValue +'</b></i>').show();
        $(window).scrollTop($('.errorMsg:first').position().top);
        errorArray.push("error");
        return false;
      }
      else if(checkVal === false){
        // alert("MAKE SURE VALUE CONTAINS ONLY NUMBERS !!");
        errorMessage.html('<i>Make Sure the value contains only number</i>').show();
        $(window).scrollTop($('.errorMsg:first').position().top);
        errorArray.push("error");
        return false;
      }
      if(scoreVal !='' && scoreLength > maxValueLength){
        errorMessage.html('<i>Make Sure the value is no more than '+maxValueLength+' characters</i>').show();
        $(window).scrollTop($('.errorMsg:first').position().top);
        errorArray.push("error");
        return false;
      }
      else{
        // Here, element is a JavaScript (not jQuery) reference to each item in the selector
        scoreVal = parseInt(scoreVal);
        /*
        if the value entered is less then the min value 
        if the value entered is more then the max value 
        if the value entered is empty or not a number 
        */
        if(isNaN(scoreVal) === true){
          scoreVal = null;
        }
        errorMessage.hide();
        attempts[plID].push(scoreVal);
      }
    });

    var insertAllDayScore = {
      "attempts" : attempts,
      "gameID" : gameID,
      "date" : date
    }

    var checkIfScoresExist = {
      "gameID" : gameID,
      "date" : date
    }

    var updateAllExistDayScore = {
      "attempts" : attempts,
      "gameID" : gameID,
      "date" : date
    }

    if(errorArray.length === 0 ){
      dateError.hide();
      $.post('includes/databaseFunctions.php',// url
        // data to be submit
        {checkIfScoresExist},  // data to be submit
        function(data, status, jqXHR) 
        {// success callback
          var objJSON = JSON.parse(data);

          if(objJSON['success'] === 0){
            if(confirm("ALL SCORE WILL BE LIVE !!")){
              $('#iziModal').iziModal('open');
              //if there's no score for this game and date add it
              $.post('includes/databaseFunctions.php',// url
              // data to be submit
              {insertAllDayScore},  // data to be submit
              function(data, status, jqXHR) 
              {// success callback
                var objJSON = JSON.parse(data);

                if(objJSON['success'] === 0){
                  alert(objJSON['msg']);
                }else{
                  location.href = "displayGame.php?processType=game&gameID="+gameID+"&Updated";
                }
              })
              .fail(function(jqxhr, status, ex)
                { alert('failed '+ ex);
                });
            }else{
              e.preventDefault();
            }
          }
          else{
            if(confirm("THERE'S PREVIOUS SCORES FOR THIS DATE. YOU SURE YOU WANT TO UPDATE ALL SCORE??")){
              $('#iziModal').iziModal('open');
              //if there's no score for this game and date add it
              $.post('includes/databaseFunctions.php',// url
              // data to be submit
              {updateAllExistDayScore},  // data to be submit
              function(data, status, jqXHR) 
              {// success callback
                var objJSON = JSON.parse(data);

                if(objJSON['success'] === 0){
                  alert(objJSON['msg']);
                }else{
                  location.href = "displayGame.php?processType=game&gameID="+gameID+"&Updated";
                }
              })
              .fail(function(jqxhr, status, ex)
                { alert('failed '+ ex);
                });
            }else{
              e.preventDefault();
            }
          }
        })
        .fail(function(jqxhr, status, ex)
          { alert('failed '+ ex);
          });
    }
   })
  }
 
  //add single points button
  $(".addSinglePoints").on("click", function(e) {
    e.preventDefault();

    var today = new Date();
    var dd = String(today.getDate()).padStart(2, '0');
    var mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
    var yyyy = today.getFullYear();
    today = yyyy + '-' + mm + '-' + dd ;

    const clickedButton = $(this);
    var ajaxLoader = clickedButton.siblings('#ajaxLoader');
    const errorMessage = clickedButton.parent().parent().siblings('.errorMsg');
    const editButton = $(this).siblings('.editSinglePoints');
    const updateButton = $(this).siblings('.updateSinglePoints');
    const hideInput = $(this).parent().siblings('.pointsInput').children('.scoreAttempt');
    const showDailyScore = $(this).parent().siblings('.pointsInput').children('.showDailyScore');

    //get the player ID
    let playerIDVar = $(this).parent().parent().attr('id');
    var playerID = playerIDVar.substring(5);
    let gameId = $('.ViewGameInfo').attr('id');
    let date = $("#dateScore").val();
    var startDate = $('.startDate').html();
    var endDate = $('.endDate').html();
    const minValue = parseInt($('.scoreAttempt').attr('min'));
    const maxValue = parseInt($('.scoreAttempt').attr('max'));
    var attempts = [];
    var errorArray = [];
    var emptyDateError = $('.badMessageEmptyScore');

    //check if date is empty
    if(date == ''){
      emptyDateError.html('MAKE SURE DATE IS SELECTED').show();
      errorArray.push("error"); 
      return false;
    }

    // Checks for the following valid date formats:
    //YYYY/MM/DD
    var datePat = /^\d{4}-\d{2}-\d{2}$/;
    var matchArray = date.match(datePat); // is the format ok?
    if (matchArray == null) {
      dateError.html('MAKE SURE DATE IS IN YYYY-MM-DD FORMAT').show();
      errorArray.push("error"); 
      return false;
    }

    if(date < startDate){
      emptyDateError.html('MAKE SURE DATE IS EQUAL OR BIGGER THAN START DATE').show();
      errorArray.push("error"); 
      return false;
    }

    if(date > endDate){
      emptyDateError.html('MAKE SURE DATE IS NOT BIGGER THAN END DATE').show();
      errorArray.push("error"); 
      return false;
    }

    clickedButton.parent().siblings('.pointsInput').children('.scoreAttempt').each(function(index, element){
       var value = $(this).val();
       var checkVal =  element.checkValidity();
       var scoreLength = value.length;
       var maxValueLength = maxValue.toString().length;

      if(value % 1 != 0){
        errorMessage.html('<i>Make Sure the value is a valid integer</i>').show();
        $(window).scrollTop($('.errorMsg:first').position().top);
        errorArray.push("error"); 
        return false;
      }
      if(value!= '' && value < minValue || value!= '' && value > maxValue){
        errorMessage.html('<i>Make Sure Value is between <br> <b>' + minValue + '</b> and <b>'+ maxValue +'</b></i>').show();
        $(window).scrollTop($('.errorMsg:first').position().top);
        errorArray.push("error");
        return false;
      }
      if(checkVal === false){
        errorMessage.html('<i>Make Sure the value contains only number</i>').show();
        $(window).scrollTop($('.errorMsg:first').position().top);
        errorArray.push("error");
        return false;
      }
      if(value !='' && scoreLength > maxValueLength){
        errorMessage.html('<i>Make Sure the value is no more than '+maxValueLength+' characters</i>').show();
        $(window).scrollTop($('.errorMsg:first').position().top);
        errorArray.push("error");
        return false;
      }
      else{
        // Here, element is a JavaScript (not jQuery) reference to each item in the selector
        value = parseInt(value);
        /*
        if the value entered is less then the min value 
        if the value entered is more then the max value 
        if the value entered is empty or not a number 
      */
        if(isNaN(value) === true){
          value = null;
        }
        attempts[index] = value;
        errorMessage.hide();
      }
    });
    
    var nroAttempts = clickedButton.parent().siblings('.pointsInput').children('.scoreAttempt').length;
    var totalDailyScore = null;
    var valEntered = 0;
    for (var i = 0; i < attempts.length; i++){
      if(attempts[i] !== null ){
        totalDailyScore += attempts[i] << 0;
        valEntered++;
      }
    }

    attemptsValueArray = attempts;

    if(errorArray.length ===0) {
      emptyDateError.hide();
      var ifScoreExist = {
        "gameID" : gameId,
        "date" : date,
        "playerID" : playerID
      }

      var addPoints = {
        "gameID" : gameId,
        "date" : date,
        "dateEntered": today,
        "playerID" : playerID,
        "totalDailyScore" : totalDailyScore,
        "attemptsValuesArray" : attemptsValueArray
      }

      var updateExistScore = {
        "gameID" : gameId,
        "date" : date,
        "dateEntered": today,
        "playerID" : playerID,
        "totalDailyScore" : totalDailyScore,
        "attemptsValuesArray" : attemptsValueArray
      }

      ajaxLoader.show();
      clickedButton.hide();
      $.post('includes/databaseFunctions.php',// url
      // check if score exists
        {ifScoreExist},  // data to be submit
        function(data, status, jqXHR)
        {// success callback
          var objJSON = JSON.parse(data);
          //if score does not exist then add it
          if(objJSON['success'] === 0){
            $.post('includes/databaseFunctions.php',// url
              // data to be submit
              {addPoints},  // data to be submit
                function(data, status, jqXHR) 
                {// success callback
                  var objJSON = JSON.parse(data);
                  if(objJSON['success'] === 0){
                    alert(objJSON['msg']);
                  }
                  else{
                    ajaxLoader.hide();
                    showDailyScore.append(objJSON['dailyScore']).show();
                    clickedButton.parent().siblings('.errorMsg').hide();
                    editButton.show();
                    updateButton.hide();
                    hideInput.hide();
                  }
                })
              .fail(function(jqxhr, settings, ex)
              { alert('failed, ' + ex); });
          }
          //if there's live score for the day,add to temporary score table
          else if(objJSON['success'] === 2){
            if(confirm(objJSON['msg'])){
              $.post('includes/databaseFunctions.php',// url
              // data to be submit
              {addPoints},  // data to be submit
                function(data, status, jqXHR) 
                {// success callbacks
                  var objJSON = JSON.parse(data);
                  if(objJSON['success'] === 0){
                    alert(objJSON['msg']);
                  }
                  else{
                  ajaxLoader.hide();
                  showDailyScore.append(objJSON['dailyScore']).show();
                  clickedButton.parent().siblings('.errorMsg').hide();
                  editButton.show();
                  updateButton.hide();
                  hideInput.hide();
                  }
                })
              .fail(function(jqxhr, settings, ex)
              { alert('failed, ' + ex); });

            }else{
              e.preventDefault();
              clickedButton.show();
              ajaxLoader.hide();
            }
          }
          //if there's Temporary Score for this day
          else if(objJSON['success'] === 3){
            if(confirm(objJSON['msg'])){
              $.post('includes/databaseFunctions.php',// url
              // data to be submit
              {updateExistScore},  // data to be submit
                function(data, status, jqXHR) 
                {// success callbacks
                  var objJSON = JSON.parse(data);
                  if(objJSON['success'] === 0){
                    alert(objJSON['msg']);
                  }
                  else{
                  ajaxLoader.hide();
                  showDailyScore.append(objJSON['dailyScore']).show();
                  clickedButton.parent().siblings('.errorMsg').hide();
                  editButton.show();
                  updateButton.hide();
                  hideInput.hide();
                }
                })
              .fail(function(jqxhr, settings, ex)
              { alert('failed, ' + ex); });

            }else{
              e.preventDefault();
              clickedButton.show();
              ajaxLoader.hide();
            }

          }
          //if score exist update it
          else{
            if(confirm(objJSON['msg'])){
              $.post('includes/databaseFunctions.php',// url
              // data to be submit
              {updateExistScore},  // data to be submit
                function(data, status, jqXHR) 
                {// success callbacks
                  var objJSON = JSON.parse(data);
                  if(objJSON['success'] === 0){
                    alert(objJSON['msg']);
                  }
                  else{
                    ajaxLoader.hide();
                    showDailyScore.append(objJSON['dailyScore']).show();
                    clickedButton.parent().siblings('.errorMsg').hide();
                    editButton.show();
                    updateButton.hide();
                    hideInput.hide();
                  }
                })
              .fail(function(jqxhr, settings, ex)
              { alert('failed, ' + ex); });

            }else{
              e.preventDefault();
              clickedButton.show();
              ajaxLoader.hide();
            }
          }
        }
      )
      .fail(function(jqxhr, settings, ex)
      { alert('failed, ' + ex); });
    }
  });

  //edit single points button
  $(".editSinglePoints").on("click", function(e) {
    e.preventDefault();
    $(this).parent().siblings('.pointsInput').children('.showDailyScore').hide();
    $(this).hide();
    const updateButton = $(this).siblings('.updateSinglePoints');
    $(this).parent().siblings('.pointsInput').children('.label').show();
    $(this).parent().siblings('.pointsInput').children('.scoreAttempt').show(); 
    $(this).siblings('.updateSinglePoints').show();
    var attempts = [];

    $(this).siblings('.updateSinglePoints').parent().siblings('.scoreAttempt').each(function(index, element){
      //Here, element is a JavaScript (not jQuery) reference to each item in the selector
      //get the values of the input
      attempts[index] = $( this ).val();
      updateButton.show();
    });
  });

  //update single points button
  $(".updateSinglePoints").on("click", function(e) {
    e.preventDefault();

    const clickedButton = $(this);
    var ajaxLoader = clickedButton.siblings('#ajaxLoader');
    const errorMessage = clickedButton.parent().parent().siblings('.errorMsg');
    const hideInput = $(this).parent().siblings('.pointsInput').children('.scoreAttempt');
    const hideLabel = $(this).parent().siblings('.pointsInput').children('.label');
    const showDailyScore = $(this).parent().siblings('.pointsInput').children('.showDailyScore');
    const editButton = $(this).siblings('.editSinglePoints');
    var minValue =  parseInt($('.scoreAttempt').attr('min'));
    var maxValue =  parseInt($('.scoreAttempt').attr('max'));
    let gameId = $('.ViewGameInfo').attr('id');
    let date = $("#dateScore").val();
    let playerIDVar = $(this).parent().parent().attr('id');
    var playerID = playerIDVar.substring(5);
    var attempts = [];
    var errorArray = [];

    clickedButton.parent().siblings('.pointsInput').children('.scoreAttempt').each(function(index, element){
      // Here, element is a JavaScript (not jQuery) reference to each item in the selector
      var value = $(this).val();
      var checkVal =  element.checkValidity();
      var scoreLength = value.length;
      var maxValueLength = maxValue.toString().length;

      if(value % 1 != 0){
        errorMessage.html('<i>Make Sure the value is a valid integer</i>');
        errorArray.push("error");
          return false;
      }else if(value < minValue || value > maxValue){
        errorMessage.html('<i>Make Sure Value is between <br> <b>' + minValue + '</b> and <b>'+ maxValue +'</b></i>').show();
        errorArray.push("error");
        return false;
      }else if(checkVal === false){
        errorMessage.html('<i>Make Sure the value contains only number</i>').show();
        errorArray.push("error");
        return false;
      }
      if(value !='' && scoreLength > maxValueLength){
        errorMessage.html('<i>Make Sure the value is no more than '+maxValueLength+' characters</i>').show();
        $(window).scrollTop($('.errorMsg:first').position().top);
        errorArray.push("error"); 
        return false;
      }
      else{
        // Here, element is a JavaScript (not jQuery) reference to each item in the selector
        value = parseInt(value);
        
        /*
          if the value entered is less then the min value 
          if the value entered is more then the max value 
          if the value entered is empty or not a number 
        */

        if(isNaN(value) === true){
            value = null;
        }
        
        attempts[index] = value;
        errorMessage.hide();
      }
    });

    var nroAttempts =  clickedButton.parent().siblings('.pointsInput').children('.scoreAttempt').length;
    clickedButton.parent().siblings('.pointsInput').children('.scoreAttempt').length;

    var totalDailyScore = null;
    for (var i = 0; i < attempts.length; i++){
      if(attempts[i] !== null ){
        totalDailyScore += attempts[i] << 0;
      }
    }

    var attemptsValueArray = attempts;

    if(errorArray.length===0) {
      var updatePoints = {
        "gameID" : gameId,
        "playerID" : playerID,
        "date" : date ,
        "totalDailyScore" : totalDailyScore,
        "attemptsValuesArray" : attemptsValueArray
      }

      ajaxLoader.show();
      clickedButton.hide();
      $.post('includes/databaseFunctions.php',// url
      // data to be submit
      {updatePoints},  // data to be submit
      function(data, status, jqXHR) 
      {// success callback
        var objJSON = JSON.parse(data);
        if(objJSON['success'] === 0){
          alert(objJSON['msg']);
        }
        else{
          ajaxLoader.hide();
          showDailyScore.html(objJSON['dailyScore']).show();
          editButton.show();
          hideInput.hide();
          hideLabel.hide();
        }
      }
      )
      .fail(function(jqxhr, settings, ex)
      { alert('failed, ' + ex); });
    }
  });

  ////////////// edit game day ////////////////////////////

  //when update all Points button is pressed to enter all the scores
  var updateAllEditedPoints = document.getElementById('updateAllEditedPoints');
  if(updateAllEditedPoints){
    updateAllEditedPoints.addEventListener("click", function(e){
      var gameID = $(".ViewGame").attr("id");
      var gameDate = $('.ViewGame').data('date');
      var minValue =  parseInt($('.scoreAttempt').attr('min'));
      var maxValue =  parseInt($('.scoreAttempt').attr('max'));
      var attempts = [];
      var errorArray = [];

      if( $('.scoreAttempt:visible').length > 0){
        $('.scoreAttempt:visible').each(function(index, element){
          var plID = $(this).siblings('.showDailyScore').attr("id");

          if(typeof attempts[plID] === 'undefined'){
            attempts[plID] = [];
          }
          
          var errorMessage = $(this).parent().parent().siblings('.errorMsg');
          var scoreVal = $(this).val();
          var scoreLength = scoreVal.length;
          var maxValueLength = maxValue.toString().length;
          var checkVal =  element.checkValidity();

          if(scoreVal % 1 != 0){
            errorMessage.html('<i>Make Sure the value is a valid integer</i>').show();
            $(window).scrollTop($('.errorMsg:first').position().top);
            errorArray.push("error"); 
            return false;
          }
          if(scoreVal!='' && scoreVal < minValue || scoreVal!='' && scoreVal > maxValue){
          errorMessage.html('<i>Make Sure Value is between <br> <b>' + minValue + '</b> and <b>'+ maxValue +'</b></i>').show();
          $(window).scrollTop($('.errorMsg:first').position().top);
          errorArray.push("error");
          return false;
          }
          if(checkVal === false){
          errorMessage.html('<i>Make Sure the value contains only number</i>').show();
          $(window).scrollTop($('.errorMsg:first').position().top);
          errorArray.push("error");
          return false;
          } 
          if(scoreVal !='' && scoreLength > maxValueLength){
            errorMessage.html('<i>Make Sure the value is no more than '+maxValueLength+' characters</i>').show();
            $(window).scrollTop($('.errorMsg:first').position().top);
            errorArray.push("error"); 
            return false;
          }
          else{
            scoreVal = parseInt(scoreVal);
            /*
            if the value entered is less then the min value 
            if the value entered is more then the max value 
            if the value entered is empty or not a number 
            */
            if(isNaN(scoreVal) === true){
                scoreVal = null;
            }
            errorMessage.hide();
            attempts[plID].push(scoreVal);
          }
        });

        var updateAllEditScore = {
        "attempts" : attempts,
        "date" : gameDate,
        "gameID" : gameID,
        }

        if(errorArray.length ===0 ){
          if(confirm('ALL SCORE WILL BE UPDATED LIVE !!')){
            $('#iziModal').iziModal('open');
              $.post('includes/databaseFunctions.php',// url
              // data to be submit
              {updateAllEditScore},  // data to be submit
              function(data, status, jqXHR) 
              {// success callback
                var objJSON = JSON.parse(data);
                console.log(objJSON);
                if(objJSON['success'] === 0){
                  alert(objJSON['ERRORmessage']);
                }else{
                  location.href = "displayGame.php?processType=game&gameID="+gameID+"&Edited";
                }
              })
              .fail(function(jqxhr, settings, ex)
              { alert('failed, ' + ex); });
          }
        }
        else{
          e.preventDefault();
        }
      }
      else{
        $('.showDailyScore').each(function(index, element){
          var value = $(this).html();
          var plID = $(this).attr('id');
          
          if(typeof attempts[plID] === 'undefined'){
            attempts[plID] = [];
            }
          attempts[plID].push(value);
        })

        var updateAllEditScore = {
          "attempts" : attempts,
          "date" : gameDate,
          "gameID" : gameID,
          }

        if(confirm('ALL SCORE WILL BE UPDATED LIVE !!')){
          $('#iziModal').iziModal('open');
          $.post('includes/databaseFunctions.php',// url
          // data to be submit
          {updateAllEditScore},  // data to be submit
          function(data, status, jqXHR) 
          {// success callback
            var objJSON = JSON.parse(data);

            if(objJSON['success'] === 0){
              alert(objJSON['msg']);
            }else{
              location.href = "displayGame.php?processType=game&gameID="+gameID+"&Updated";
            }
          })
          .fail(function(jqxhr, status, ex)
            { alert('failed '+ ex);
            });

        }
        else{
        e.preventDefault();
        }
      }
    });
  }

  //when addSinglePointsDay button is clicked
  $(".addSinglePointsDay").on("click", function(e) {
    e.preventDefault();
    //prevent Enter or Return button to submit values only when add/update/edit button is allowed to be clicked
    $(document).keydown(function(e){
      if(e.keyCode == 13) {
      e.preventDefault();
      return false;
      }
    });

    const clickedButton = $(this);
    var ajaxLoader = clickedButton.siblings('#ajaxLoader');
    const errorMessage = clickedButton.parent().parent().siblings('.errorMsg');
    const editButton = $(this).siblings('.editSinglePointsDay');
    const updateButton = $(this).siblings('.updateSinglePointsDay');
    const hideInput = $(this).parent().siblings('.pointsInput').children('.scoreAttempt');
    const showDailyScore = $(this).parent().siblings('.pointsInput').children('.showDailyScore')
    let playerIDVar = $(this).parent().parent().attr('id');
    var playerID = playerIDVar.substring(5);
    let gameId = $('.ViewGameInfo').attr('id');
    var gameDate = $('.ViewGame').data('date');
    const minValue = parseInt($('.scoreAttempt').attr('min'));
    const maxValue = parseInt($('.scoreAttempt').attr('max'));
    var attempts = [];
    var errorArray = [];

    clickedButton.parent().siblings('.pointsInput').children('.scoreAttempt').each(function(index, element){
      var value = $(this).val();
      var checkVal =  element.checkValidity();
      var scoreLength = value.length;
      var maxValueLength = maxValue.toString().length;

      if(value % 1 != 0){
        errorMessage.html('<i>Make Sure the value is a valid integer</i>').show();
        errorArray.push("error"); 
        return false;
      }
      if(value!= '' && value < minValue || value!= '' && value > maxValue){
        errorMessage.html('<i>Make Sure Value is between <br> <b>' + minValue + '</b> and <b>'+ maxValue +'</b></i>').show();
        errorArray.push("error");
        return false;
      }
      if(checkVal === false){
        errorMessage.html('<i>Make Sure the value contains only number</i>').show();
        errorArray.push("error");
        return false;
      }
      if(value !='' && scoreLength > maxValueLength){
        errorMessage.html('<i>Make Sure the value is no more than '+maxValueLength+' characters</i>').show();
        $(window).scrollTop($('.errorMsg:first').position().top);
        errorArray.push("error"); 
        return false;
      }
      else{
        // Here, element is a JavaScript (not jQuery) reference to each item in the selector
        value = parseInt(value);
        /*
        if the value entered is less then the min value 
        if the value entered is more then the max value 
        if the value entered is empty or not a number 
      */
        if(isNaN(value) === true){
            value = null;
        }
        attempts[index] = value;
        errorMessage.hide();
      }
    });
  
    var nroAttempts = clickedButton.parent().siblings('.pointsInput').children('.scoreAttempt').length;
    var DailyScore = null;
    for (var i = 0; i < attempts.length; i++){
    if(attempts[i] !== null){
        DailyScore += attempts[i] << 0;
    }
    }
    attemptsValueArray = JSON.stringify(attempts);

    if(errorArray.length ===0) {
      var addPointsDay = {
      "gameID" : gameId,
      "playerID" : playerID,
      "DailyScore" : DailyScore,
      "attemptsValuesArray" : attemptsValueArray,
      "gameDate" : gameDate
      }

      ajaxLoader.show();
      clickedButton.hide();
      $.post('includes/databaseFunctions.php',// url
        // data to be submit
        {addPointsDay},  // data to be submit
        function(data, status, jqXHR) 
        {// success callback
          var objJSON = JSON.parse(data);
          showDailyScore.append(objJSON['DailyScore']).show();
          ajaxLoader.hide();
          clickedButton.parent().siblings('.errorMsg').hide();
          editButton.show();
          updateButton.hide();
          hideInput.hide();
        }
      )
      .fail(function(jqxhr, settings, ex)
      { alert('failed, ' + ex); });
    }
  });

  //when editSinglePointsDay button is clicked
  $(".editSinglePointsDay").on("click", function(e) {
    e.preventDefault();
    clickedButton = $(this);
    clickedButton.parent().siblings('.pointsInput').children('.showDailyScore').hide();
    clickedButton.hide();
    const updateButton =  clickedButton.siblings('.updateSinglePointsDay');
    clickedButton.parent().siblings('.pointsInput').children('.label').show();
    clickedButton.parent().siblings('.pointsInput').children('.scoreAttempt').show(); 
    clickedButton.siblings('.updateSinglePointsDay').show();
    var attempts = [];
    clickedButton.siblings('.updateSinglePointsDay').parent().siblings('.scoreAttempt').each(function(index, element){
      // Here, element is a JavaScript (not jQuery) reference to each item in the selector
      //get the values of the input
      attempts[index] = $( this ).val();
      updateButton.show();
    });
  });

  //when updateSinglePointsDay button is clicked
  $(".updateSinglePointsDay").on('click',function(e) {
    e.preventDefault();
    // prevent Enter or Return button to submit values only when add/update/edit button is allowed to be clicked
    $(document).keydown(function(e){
    if(e.keyCode == 13) {
        e.preventDefault();
        return false;
    }
    });
      
    const clickedButton = $(this);
    var ajaxLoader = clickedButton.siblings('#ajaxLoader');
    const errorMessage = clickedButton.parent().parent().siblings('.errorMsg');
    var hideInput = $(this).parent().siblings('.pointsInput').children('.scoreAttempt');     
    const showDailyScore = $(this).parent().siblings('.pointsInput').children('.showDailyScore');
    const editButton = $(this).siblings('.editSinglePointsDay');
    var minValue =  parseInt($('.scoreAttempt').attr('min'));
    var maxValue =  parseInt($('.scoreAttempt').attr('max'));
    let gameId = $('.ViewGameInfo').attr('id');
    var gameDate = $('.ViewGame').data('date');
    let playerIDVar = $(this).parent().parent().attr('id');
    var playerID = playerIDVar.substring(5);
    var attempts = [];
    var errorArray = [];

    clickedButton.parent().siblings('.pointsInput').children('.scoreAttempt').each(function(index, element){
      // Here, element is a JavaScript (not jQuery) reference to each item in the selector
      var value = $(this).val();
      var checkVal =  element.checkValidity();
      var scoreLength = value.length;
      var maxValueLength = maxValue.toString().length;

        if(value % 1 != 0){
        alert("MAKE SURE YOU ENTER A VALID INTEGER !!");
        errorMessage.html('<i>Make Sure the value is a valid integer</i>');
        errorArray.push("error");
          return false;
      } 
      if(value < minValue || value > maxValue){
        alert("MAKE SURE VALUE IS BETWEEN " + minValue +" & "+ maxValue+" !!");
        errorMessage.html('<i>Make Sure Value is between <br> <b>' + minValue + '</b> and <b>'+ maxValue +'</b></i>').show();
        errorArray.push("error");
        return false;
      } 
      if(checkVal === false){
        alert("MAKE SURE VALUE ONLY CONTAIN NUMBER !!");
        errorMessage.html('<i>Make Sure the value contains only number</i>').show();
        errorArray.push("error");
        return false;
      }
      if(value !='' && scoreLength > maxValueLength){
        errorMessage.html('<i>Make Sure the value is no more than '+maxValueLength+' characters</i>').show();
        $(window).scrollTop($('.errorMsg:first').position().top);
        errorArray.push("error"); 
        return false;
      }
      else{
        // Here, element is a JavaScript (not jQuery) reference to each item in the selector
        value = parseInt(value);
        
        if(isNaN(value) === true){
            value = null;
        }
        attempts[index] = value;
      }
    });

    var nroAttempts =  clickedButton.parent().siblings('.pointsInput').children('.scoreAttempt').length;
    var DailyScore = null;
    var valEntered = 0;
    for (var i = 0; i < attempts.length; i++){
      if(attempts[i] !== null ){
        DailyScore += attempts[i] ;
        valEntered++;
      }
    }
    var attemptsValuesArray = JSON.stringify(attempts);

    if(errorArray.length===0){
      var updatePointsDay = {
          "gameID" : gameId,
          "playerID" : playerID,
          "DailyScore" : DailyScore,
          "attemptsValuesArray" : attemptsValuesArray,
          "gameDate" : gameDate 
      }
        
      ajaxLoader.show();
      clickedButton.hide();
      $.post('includes/databaseFunctions.php',// url
      // data to be submit
          {updatePointsDay},  // data to be submit
          function(data, status, jqXHR) 
          {// success callback
            var objJSON = JSON.parse(data);
            ajaxLoader.hide();
            showDailyScore.html(objJSON['dailyScore']).show();
            editButton.show();
            hideInput.hide();
            errorMessage.hide();
          }
      )
      .fail(function(jqxhr, settings, ex)
      { alert('failed, ' + ex); });
    }
  });
});