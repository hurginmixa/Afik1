echo user mixa@afik1.sal-chronus.co.il Mar11_1968 > send_ftp.z
echo cd /Local_FS/afik1/src >> send_ftp.z
echo put %1 >> send_ftp.z
rem echo %PATH%
c:\winnt\system32\ftp.exe -n afik1.sal-chronus.co.il < send_ftp.z
del send_ftp.z
