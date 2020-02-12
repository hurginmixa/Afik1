// ---------------------------------------------------------------

function SelectAllTagATT(e)
{
  var elements = window.document.forms[1].elements;
  var len = elements.length;
  var i;

  for (i = 0; i < len; i++) {
    if(elements["TagATT[" + i + "]"]) {
      elements["TagATT[" + i + "]"].checked = e;
    }
  }
}

function isSelectedAllTagATT(e)
{
  var elements = window.document.forms[1].elements;
  var len = elements.length;
  var i;

  NumberOfTags = 0;
  for (i = 0; i < len; i++) {
    if(elements["TagATT[" + i + "]"]) {
      NumberOfTags++;
      if(!elements["TagATT[" + i + "]"].checked) {
        return 0;
      }
    }
  }

  if(NumberOfTags == 0) {
    return 0;
  }

  return 1;
}

function onTagATTAllClick()
{
  var elements = window.document.forms[1].elements;
  SelectAllTagATT(elements["TagATTAll"].checked);
}

function onTagATTClick()
{
  var elements = window.document.forms[1].elements;
  elements["TagATTAll"].checked = isSelectedAllTagATT();
  // alert("onTagATTClick");
}


// ---------------------------------------------------------------
function SelectAllTagMSG(e)
{
  var elements = window.document.forms[0].elements;
  var len = elements.length;
  var i;

  for (i = 0; i < len; i++) {
    if(elements["TagMSG[" + i + "]"]) {
      elements["TagMSG[" + i + "]"].checked = e;
    }
  }
}

function isSelectedAllTagMSG(e)
{
  var elements = window.document.forms[0].elements;
  var len = elements.length;
  var i;

  NumberOfTags = 0;
  for (i = 0; i < len; i++) {
    if(elements["TagMSG[" + i + "]"]) {
      NumberOfTags++;
      if(!elements["TagMSG[" + i + "]"].checked) {
        return 0;
      }
    }
  }

  if(NumberOfTags == 0) {
    return 0;
  }

  return 1;
}

function onTagMSGAllClick()
{
  var elements = window.document.forms[0].elements;
  SelectAllTagMSG(elements["TagMSGAll"].checked);
}

function onTagMSGClick()
{
  var elements = window.document.forms[0].elements;
  elements["TagMSGAll"].checked = isSelectedAllTagMSG();
  // alert("onTagMSGClick");
}



// ---------------------------------------------------------------
function SubViewMessage(url)
{
  window.open(url);
  //setTimeout("document.location = document.location;", 1000);
}

function refresh_opener()
{
        //try {
                if (!window.opener || !window.opener.document) {
                        return;
                }

                openerUrl = window.opener.document.location.href;
                selfUrl = window.document.location.href;

                //alert( selfUrl.indexOf(openerUrl) );
                if (selfUrl.indexOf(openerUrl) == 0) {  // в отцовском окне url короче, в дочернем к нему пристегнут
                                                        // параметер номера письма.
                        //alert(window.opener.document.forms.length);

                        window.opener.document.forms[0].sRefresh.value = "refresh";
                        window.opener.document.forms[0].submit();
                }
        //}
        //catch(e) {
        //}
}

function ChangeDir(dir)
{
  window.location.href = dir + "&sChangeDir=on"+ GetTagMSGSelectedList();
}


function wUpld(url)
{
  window.open(url, "", "status=yes,toolbar=no,menubar=no,location=no,resizable=yes");
}


function wSelAddresses(url)
{

  var s = window.document.forms[0].elements["fTO"].value;
  s = s.replace(/\%/g, "%25");
  s = s.replace(/\&/g, "%26");
  s = s.replace(/\"/g, "%22");
  s = s.replace(/\'/g, "%27");
  s = s.replace(/\+/g, "%2B");
  s = s.replace(/\</g, "%3C");
  s = s.replace(/\>/g, "%3E");
  s = s.replace(/\@/g, "%40");
  s = s.replace(/\?/g, "%3F");
  s = s.replace(/\=/g, "%3D");
  s = s.replace(/\ /g, "+");

  window.open(url + "&sNewView=" + s, "SelAddresses", "status=yes,toolbar=no,menubar=no,location=no,resizable=yes,width=500,height=300,scrollbars=yes");
}


function wFtpOpen(url)
{
  window.open(url, "", "status=yes,toolbar=yes,menubar=no,location=no,resizable=yes");
}
