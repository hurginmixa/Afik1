function Body_OnLoad()
{
        if (window.document.forms[0].pSelectTO) {
                //alert(window.document.forms[0].pSelectTO.value);
                //try {
                        TMPString = window.location.href;
                        if (TMPString.match(/Field=CC/i)) {
                                window.opener.document.forms[0].fCC.value = window.document.forms[0].pSelectTO.value;
                        } else {
                                if (TMPString.match(/Field=QUICKSHARE/i)) {
                                                window.opener.document.forms[0].fQuickPermName.value = window.document.forms[0].pSelectTO.value;
                                } else {
                                                window.opener.document.forms[0].fTO.value = window.document.forms[0].pSelectTO.value;
                                }
                        }
                //}

                //catch(e) {
                //}

                window.close();
        }
}


function PutSubmit()
{
  try {
    window.document.forms[0].pSubmit.value = 1;
    document.forms[0].submit();
  }
  catch(e) {
  }
}



function SelectAll_To_List(e)
{
    var elements = window.document.forms[0].elements;
    var len = elements.length;
    var i;

    for (i = 0; i < len; i++) {
       if(elements["To_List[" + i + "]"]) {
           elements["To_List[" + i + "]"].checked = e;
       }
    }
}



function isSelectedAll_To_List(e)
{
    var elements = window.document.forms[0].elements;
    var len = elements.length;
    var i;

    NumberOfTags = 0;
    for (i = 0; i < len; i++) {
      if(elements["To_List[" + i + "]"]) {
        NumberOfTags++;
        if(!elements["To_List[" + i + "]"].checked) {
          return 0;
        }
      }
    }

    if(NumberOfTags == 0) {
      return 0;
    }

    return 1;
}



function onTo_List_AllClick()
{
    var elements = window.document.forms[0].elements;
    SelectAll_To_List(elements["To_List_All"].checked);
}

function onTo_List_Click()
{
    // alert("onTo_List_Click");
    var elements = window.document.forms[0].elements;
    elements["To_List_All"].checked = isSelectedAll_To_List();
}
