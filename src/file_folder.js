function SendPermSubmitWait()
{
    if (document.forms[0].PermAddr.value == "-1") {
        setTimeout("SendPermSubmitWait()", 2000);
    } else {
        //document.forms[0].PermAddr.checked = document.forms[0].PermAddr.value != "";
        //alert(document.forms[0].PermAddr.checked);
        document.forms[0].submit();
    }
}


function SendPermSubmit()
{
    window.document.forms[0].PermAddr.value = "-1";
    setTimeout("SendPermSubmitWait()", 100);
    window.frames[0].PutSubmit();
}


function SelectAllTagFiles(e)
{
    var elements = window.document.forms[0].elements;
    var len = elements.length;
    var i;

    for (i = 0; i < len; i++) {
        if(elements["TagFile[" + i + "]"]) {
            elements["TagFile[" + i + "]"].checked = e;
        }
    }
}

function isSelectedAllTagFiles(e)
{
    var elements = window.document.forms[0].elements;
    var len = elements.length;
    var i;

    NumberOfTags = 0;
    for (i = 0; i < len; i++) {
        if(elements["TagFile[" + i + "]"]) {
            NumberOfTags++;
            if(!elements["TagFile[" + i + "]"].checked) {
                return 0;
            }
        }
    }

    if(NumberOfTags == 0) {
        return 0;
    }

    return 1;
}



function onTagFileAllClick()
{
    var elements = window.document.forms[0].elements;
    SelectAllTagFiles(elements["TagFileAll"].checked);
}

function onTagFileClick()
{
    var elements = window.document.forms[0].elements;
    elements["TagFileAll"].checked = isSelectedAllTagFiles();
    // alert("onTagFileClick");
}


function GetTagFileSelectedList()
{
    var elements = window.document.forms[0].elements;
    var len = elements.length;
    var i;

    var s = "";

    for (i = 0; i < len; i++) {
        if(elements["TagFile[" + i + "]"]) {
            if(elements["TagFile[" + i + "]"].checked) {
                if (s != "") {
                    s = s + ":";
                }
                s = s + elements["TagFile[" + i + "]"].value;
            }
        }
    }

    if (s != "") {
        s = "&TagFile=" + s
    }

    return s;
}


function ChangeDir(dir)
{
    window.location.href = dir + "&sChangeDir=on"+ GetTagFileSelectedList();
}


function OpenPrompt( TitleString, PromptString )
{
    window.prompt(TitleString, PromptString);
}


function wUpld(url)
{
    window.open(url, "", "status=yes,toolbar=no,menubar=no,location=yes,resizable=yes");
}


function wSelAddresses(url)
{
    var s;

    if (url.match(/Field=QUICKSHARE/i)) {
          s = window.document.forms[0].elements["fQuickPermName"].value;
    } else {
          s = window.document.forms[0].elements["fTO"].value;
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
