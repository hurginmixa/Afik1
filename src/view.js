// view.js

function buttonSubmit(form, name, value)
{
   MakeButtonBlankList();

   document.forms[form][name].value = value;
   //window.alert("" + form + " " + name  + " " + value);


   rez = 1;
   if (document.forms[form].onsubmit) {
     rez = document.forms[form].onsubmit();
   }

   document.forms[form].submit()

   // document.forms[form][name].value = "";
}
