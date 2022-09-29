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

  //delete game button (future games)
  $(".deleteFutureGames").on("click", function(e){
    var gameID = $('.gameInfos').attr('id');
    var toDelete = {
      "gameID": gameID
    }
      
    if(confirm("Are You Sure ???")){
      $('#iziModal').iziModal('open');
      $.post('includes/databaseFunctions.php',// url
        // data to be submit
        {toDelete},  // data to be submit
        function(data, status, jqXHR) {// success callback
          var objJSON = JSON.parse(data);
          if(objJSON['success'] === 0){
          alert(objJSON['msg']);
          loading
          }else{
            location.href = "viewGames.php?processType=deleted";
          }
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