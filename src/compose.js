function SelectAllCheckToAttach(e)
{

    var elements = window.document.forms[0].elements;
    var len = elements.length;
    var i;
    //alert(elements["CheckToAttachAll"].checked);
    //alert(len);

    for (i = 0; i < len; i++) {
        if(elements["CheckToAttach[" + i + "]"]) {
            elements["CheckToAttach[" + i + "]"].checked = e;
        }
    }
}


function isSelectedAllCheckToAttach(e)
{
    var elements = window.document.forms[0].elements;
    var len = elements.length;
    var i;

    NumberOfTags = 0;
    for (i = 0; i < len; i++) {
        if(elements["CheckToAttach[" + i + "]"]) {
            NumberOfTags++;
            if(!elements["CheckToAttach[" + i + "]"].checked) {
                return 0;
            }
        }
    }

    if(NumberOfTags == 0) {
        return 0;
    }

    return 1;
}


function onCheckToAttachAllClick()
{
    var elements = window.document.forms[0].elements;
    SelectAllCheckToAttach(elements["CheckToAttachAll"].checked);
}


function onCheckToAttachClick()
{
    var elements = window.document.forms[0].elements;
    elements["CheckToAttachAll"].checked = isSelectedAllCheckToAttach();
    // alert("onTagFileClick");
}


function wUpld(url)
{
    window.open(url, "", "status=yes,toolbar=no,menubar=no,location=no,resizable=yes");
}


function wSelAddresses(url)
{
    if (url.match(/Field=CC/i)) {
        var s = window.document.forms[0].elements["fCC"].value;
    } else {
        var s = window.document.forms[0].elements["fTO"].value;
    }
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

function ChangeDir(NumFS)
{
    document.AttForm.fFS.value = NumFS;
    document.AttForm.sChangeDir.value = 1;
    document.AttForm.submit();
}

function refreshAfterUpload(FSNum)
{
    onSubmit();
    if(document.ComposeForm) {
       document.ComposeForm.sNewUpld.value = FSNum;
       document.ComposeForm.submit();
    } else {
       document.AttForm.sNewUpld.value = FSNum;
       document.AttForm.submit();
    }
}


function onSubmit()
{
	return 1;
}

var agent = "";

function onLoad()
{
    var userAgent = navigator.userAgent;

    if ( userAgent.indexOf("Opera") != -1 )      { agent = "Opera"; } else
    if ( userAgent.indexOf("MSIE") != -1 )       { agent = "MSIE";  } else
    if ( userAgent.indexOf("Netscape6") != -1 )  { agent = "NSC";   } else
    if ( userAgent.indexOf("Netscape/7") != -1 ) { agent = "NSC";   } else
    agent = "Other";
}
