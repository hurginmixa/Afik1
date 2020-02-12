package ParseHTTPPost;

$ParseHTTPPost::ContetnLength = $main::ENV{'CONTENT_LENGTH'};
$ParseHTTPPost::ContentType = $main::ENV{'CONTENT_TYPE'};
$ParseHTTPPost::Boundary = "";
@ParseHTTPPost::Vars = ();
$ParseHTTPPost::Error = "";
$ParseHTTPPost::Read::TotalRead = 0;
$ParseHTTPPost::BufferSize = 1024 * 1024;

#############################################################################
sub Debug($)
{
  open DEBUG, ">../debug";
  print DEBUG @_[0], "\n";
  close DEBUG;
}

sub Parse()
{
  my($r);

  #------------------------------------------------

  if ($ParseHTTPPost::ContetnLength <= 0) {
    MessErrorLength();
    return 0;
  }

  if( $ParseHTTPPost::ContentType =~ /multipart\/form-data; boundary=(.+)$/ ) {
    $ParseHTTPPost::Boundary = $1;
  } else {
    MessErrorBoundary();
    return 0;
  }

  #------------------------------------------------

  $ParseHTTPPost::Read::TotalRead = 0;
  $ParseHTTPPost::Read::LastError = 0;
  $ParseHTTPPost::FieldCount = 0;

  $buf = "\r\n";

  while (1) {

    #------------------------------------------------
    # Searching of Boundary in begin section

    $flag = ($buf =~ /\r\n--$ParseHTTPPost::Boundary/), $rr = $';
    while(!$flag) {
      if (length($buf) > length($ParseHTTPPost::Boundary) + 8) {
        $buf = substr($buf, -(length($ParseHTTPPost::Boundary) + 8))
      }


      if (Read()) {
        MessErrorMIMEStruct(1);
        return 0;
      }
      $buf .= $ParseHTTPPost::Read::Buffer;
      $flag = ($buf =~ /\r\n--$ParseHTTPPost::Boundary/), $rr = $';
    }
    
    #------------------------------------------------
    # $buf soderjit ostatik bufera posle otkusywanija BOUNDARY
    # Razbor zagolowka sekzii

    $buf = $rr;
  
    #------------------------------------------------
    # Proverka na zakrywaushii BOUNDARY
    # Pri poslednem chtenii zakluchitelnye simwoly mogli ne popast'

    if (length($buf) < 4) {
      if (Read()) {
        if ($ParseHTTPPost::Read::LastError == 2 && $buf eq "\r\n") {
          last;
        } else {
          MessErrorMIMEStruct(2);
          return 0;
        }
      }
      $buf .= $ParseHTTPPost::Read::Buffer;
    }

    if (substr($buf, 0, 4) eq "--\r\n") {
      last;
    }


    #------------------------------------------------
    # Proverka nalichija w bufere wsego zagolowka - t.e. 2-ch 
    # perewodow ctroki podrjad. Proverku na razmer ne proizwodim,
    # potomuchto razmer bloka chrtrnija, gorazdo prevoschodit rasmer zagolowka

    if (!($buf =~ /\r\n\r\n|\n\n/)) {
      if (Read()) {
        MessErrorMIMEStruct(3);
        return 0;
      }
      $buf .= $ParseHTTPPost::Read::Buffer;
    }
    
    #------------------------------------------------
    # Naidena novaja sekzija - razbor zagolowka

    $buf =~ /\r\n\r\n|\n\n/;  # Poisk konza zagolowka
    $buf = $';           # Wse chto posle - ostal'noi bufer
    $rr  = $`;           # Wse chto ran'she zagolovok

    $ParseHTTPPost::FieldCount++;

    #open f, ">rrr_${ParseHTTPPost::FieldCount}_$$.h";
    #binmode(f);
    #syswrite(f, $rr, length($rr));
    #close f;

    $FieldName = "";
    $FileName  = "";
    $ContType  = "";

    #Debug($rr);


    if ($rr =~ /Content-Disposition:[ ]*form-data;[ ]*name[ ]*=[ ]*([^;\r\n]*)/i) {
      $FieldName = $1;
      $FieldName = $FieldName =~ /"([^\"]*)"/ ? $1 : $FieldName;
    } else {
      MessErrorMIMEStruct(4);
      return 0;
    }

    if ($rr =~ /filename[ ]*=[ ]*([^;\r\n]*)/i) {
      $FileName = $1;
      $FileName = $FileName =~ /"([^\"]*)"/ ? $1 : $FileName;
      $FileName = $FileName =~ /([^\\]*)$/ ? $1 : $FileName;
    }

    if ($rr =~ /Content-Type[ ]*:[ ]*([^;\r\n]*)/i) {
      $ContType = $1;
      $ContType = $ContType =~ /"([^\"]*)"/ ? $1 : $ContType;
    }

    #print main::STDERR "=$FieldName=$FileName=$ContType=\n";

    $ParseHTTPPost::Vars[$ParseHTTPPost::FieldCount - 1]{Name}          = $FieldName;
    $ParseHTTPPost::Vars[$ParseHTTPPost::FieldCount - 1]{Tmp_File_Name} = "${main::PROGRAM_FILES}/parseHTTP_rrr_${ParseHTTPPost::FieldCount}_$$";
    $ParseHTTPPost::Vars[$ParseHTTPPost::FieldCount - 1]{File_Name}     = $FileName;
    $ParseHTTPPost::Vars[$ParseHTTPPost::FieldCount - 1]{Content_Type}  = $ContType;

    #------------------------------------------------

    open f, ">${ParseHTTPPost::Vars[$ParseHTTPPost::FieldCount - 1]{Tmp_File_Name}}";
    binmode(f);

    $flag = ($buf =~ /\r\n--$ParseHTTPPost::Boundary/), $rr = $`;
    while(!$flag) {

      if (length($buf) > length($ParseHTTPPost::Boundary) + 8) {
        syswrite(f, $buf, length($buf) - (length($ParseHTTPPost::Boundary) + 8));
        $buf = substr($buf, -(length($ParseHTTPPost::Boundary) + 8))
      }

      if (Read()) {
        MessErrorMIMEStruct(5);
        close f;
        return 0;
      }
      $buf .= $ParseHTTPPost::Read::Buffer;
      $flag = ($buf =~ /\r\n--$ParseHTTPPost::Boundary/), $rr = $`;
    }
    
    syswrite(f, $rr, length($rr));

    close f;
  }

  #print main::STDERR ">$res<>$ParseHTTPPost::Read::LastError<\n";

#  if ($ParseHTTPPost::Read::LastError) {
#    return 0;
#  }

  return 1;
}


#############################################################################

sub Read()
{
  if ($ParseHTTPPost::Read::LastError) {
    return $ParseHTTPPost::Read::LastError;
  }

  if ($ParseHTTPPost::Read::TotalRead >= $ParseHTTPPost::ContetnLength) {
    $ParseHTTPPost::Read::LastError = 2;
    return $ParseHTTPPost::Read::LastError;
  }

  $d = (($ParseHTTPPost::ContetnLength - $ParseHTTPPost::Read::TotalRead) < $ParseHTTPPost::BufferSize ? ($ParseHTTPPost::ContetnLength - $ParseHTTPPost::Read::TotalRead) : $ParseHTTPPost::BufferSize);
  my($cikl) = 2;
  while ((!($res = read(main::STDIN, $ParseHTTPPost::Read::Buffer, $d))) && $cikl) {
    Debug("=$res=$cikl=$d=");
    $cikl--;
  }
  if (!$cikl) {
    $ParseHTTPPost::Read::LastError = 1;
    return $ParseHTTPPost::Read::LastError;
  }

  #print main::STDERR "$ParseHTTPPost::Read::TotalRead $res $ParseHTTPPost::ContetnLength " . ($ParseHTTPPost::ContetnLength - $ParseHTTPPost::Read::TotalRead) . "\n";

  if ($res != 0) {
    $ParseHTTPPost::Read::TotalRead += $res;
  }
  
  $ParseHTTPPost::Read::LastError = 0;
  return $ParseHTTPPost::Read::LastError;
}


sub ReadAll()
{
  while (!$ParseHTTPPost::Read::LastError) {
    Read();
  }
}


#############################################################################

sub MessErrorDBConnect()
{
  ReadAll();
  $ParseHTTPPost::error = "Connect to DB failed.";
}

sub MessErrorLogin($)
{
  my($ErrNo) = @_;
  ReadAll();
  $ParseHTTPPost::Error = "Idenfification failed. ErrNo $ErrNo.";
}

sub MessErrorInvalidMethod()
{
  ReadAll();
  $ParseHTTPPost::Error = "Error method.";
}

sub MessErrorLength()
{
  $ParseHTTPPost::Error = "Error Length.";
}

sub MessErrorContentType()
{
  my($pr);
  ReadAll();
  $ParseHTTPPost::Error =  "Error Content-Type $pr.";
}

sub MessErrorBoundary()
{
  ReadAll;
  $ParseHTTPPost::Error =  "No Math Boundary.";
}

sub MessErrorMIMEStruct($)
{
  ReadAll;
  my($ErrNo) = @_;
  $ParseHTTPPost::Error = "Error MIME Structure: $ErrNo $ParseHTTPPost::Read::LastError $ParseHTTPPost::ContetnLength - $ParseHTTPPost::Read::TotalRead.";
}

sub MessErrorInternalError($)
{
  my($ErrNo) = @_;
  ReadAll;
  $ParseHTTPPost::Error =  "InternalError N $ErrNo.";
}

#############################################################################

1;
