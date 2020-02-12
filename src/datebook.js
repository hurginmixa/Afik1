function SelectAll_Select(e)
{
    var elements = window.document.forms[0].elements;
    var len = elements.length;
    var i;

    for (i = 0; i < len; i++) {
       if(elements["Select[" + i + "]"]) {
           elements["Select[" + i + "]"].checked = e;
       }
    }
}



function isSelectedAll_Select(e)
{
    var elements = window.document.forms[0].elements;
    var len = elements.length;
    var i;

    NumberOfTags = 0;
    for (i = 0; i < len; i++) {
      if(elements["Select[" + i + "]"]) {
        NumberOfTags++;
        if(!elements["Select[" + i + "]"].checked) {
          return 0;
        }
      }
    }

    if(NumberOfTags == 0) {
      return 0;
    }

    return 1;
}


function onSelect_AllClick()
{
    var elements = window.document.forms[0].elements;
    SelectAll_Select(elements["Select_All"].checked);
}


function onSelect_Click()
{
    // alert("onSelect_Click");
    var elements = window.document.forms[0].elements;
    elements["Select_All"].checked = isSelectedAll_Select();
}

