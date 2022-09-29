
 $(document).ready(function(){
  //when select game type is changed, created a input with the data-settings
  $('.selectGameType').on('change', function(){
    var settings = $(this).children("option:selected").data("settings");
    var text = "";
    for(var item in settings){
      if(item === "rules"){
        continue;
      }

      var attrText = "";
      let type 		= settings[item]["type"];
      let name 		= settings[item]["name"];
      let attributesArray	= settings[item]["attributes"];
      let value       = settings[item]["enteredValue"] ? settings[item]["enteredValue"] : "" ;

      for(var key in attributesArray){
        if(attributesArray[key]===false){
          continue;
        }
        attrText += key + " = '"+attributesArray[key]+"' "; 
      }

      switch(type){
        case "number":
        text    += "<label class='labelInput'>"+item+" :";
        text    +=  "<input name='"+name+"' id='"+name+"'  value='"+value+"' type='number' "+attrText+" class='input setting' />";
        text    += "</label>";
        break;

        case "text":
        break;
        default :
        return false;
      }
    }
    document.getElementById("hiddenInfo").innerHTML = text;
  });
  $( ".selectGameType" ).trigger('change');

  //if the button select all player is pressed select all the span with class check
  $(function(){
  $('.selAll').on('click', function() {
     $('.checkbox').prop('checked', this.checked);
  });
  });

  //when create or edit tournament submit is pressed
  $(function(){
    $(".form").on("submit", function() {
      var  usedDefaultValue = false;

      var settings = $(".selectGameType option:selected").data("settings");
      for(var item in settings){
        if(item === "rules"){
          continue;
        }

        if($("#"+item).val().length == 0){
          usedDefaultValue = true;
        }
      }

      if(usedDefaultValue == true){
        if(confirm("YOU ARE ABOUT TO USE DEFAULT VALUE")){
            $(".setting").each(function() {
            if($(this).val() == "" && $(this).attr("placeholder")!=''){
              var val  = $(this).prop("placeholder");
              $(this).val(val);
            }
            });
        }
        else{
          event.preventDefault();
          return false;
        }
      }
    })
  });

  //when select player span is clicked flips the arrow 
  $(function(){
    $('.selectPlayerSpan').on('click', function() {
      $('.select').toggle();
      $('.fa-arrow-down').toggleClass("arrowUp");
    });
  });

  //check if the url contains edit future game string, if yes change the button text and href
  if(window.location.href.indexOf("editFutureGame") > -1){
    var backIndex = document.getElementById('backIndex');
    backIndex.setAttribute('href','viewGames.php');
    backIndex.innerHTML="Back";
  }
 });