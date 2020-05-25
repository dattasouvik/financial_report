@ECHO OFF
:START
CLS
ECHO Please Enter Module Name Without Spaces
SET /P module=
ECHO You Have Entered Module Name as %module%
ECHO "Please Select The Following Options Before Proceeding"
ECHO "1 | Change the module name"
ECHO "2 | I want to exit"
ECHO "3 | Please Proceed"
SET /P user_input=
ECHO You Have Entered %user_input%
IF "%user_input%"=="1" GOTO START
IF "%user_input%"=="2" GOTO END
IF "%user_input%"=="3" GOTO RESUME
:RESUME
cd D:\xampp\htdocs\my_project\web\modules\custom
mkdir  "%module%"
cd "%module%"
type NUL > "%module%".info.yml
type NUL > "%module%".routing.yml
type NUL > "%module%".permissions.yml
type NUL > "%module%".libraries.yml
type NUL > "%module%".module
mkdir "src"
mkdir "js"
cd "src"
mkdir "Form"
mkdir "Controller"
pause
ECHO 
ECHO "File Created!!!"
:END
ECHO "I am leaving"
exit