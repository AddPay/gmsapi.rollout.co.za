unit atomwebservice;



interface

uses
  Windows, Messages, SysUtils, Classes, Graphics, Controls, Forms,
  IdHTTP, StdCtrls, ExtCtrls,IdGlobal, variants,  winsock,
  ulkjson;

type
  THttpPostForm = class(TForm)
    ToolsPanel: TPanel;
    DisplayMemo: TMemo;
    Label3: TLabel;
    ActionURLEdit: TEdit;
    PostButton: TButton;
    Label1: TLabel;
    GetUserURL: TEdit;
    Button1: TButton;
    Label2: TLabel;
    UpdatePersonEdit: TEdit;
    Button2: TButton;
    PersonBody: TMemo;
    Label4: TLabel;
    AddPersonEdit: TEdit;
    Button3: TButton;
    procedure PostButtonClick(Sender: TObject);
    procedure Button1Click(Sender: TObject);
    procedure Button2Click(Sender: TObject);
    procedure Button3Click(Sender: TObject);
    procedure Button4Click(Sender: TObject);

  private
    FInitialized : Boolean;
    HttpResult : string;
    function HttpGet(url:string; body:string): string;
    function HttpPost(url:string;body:string; put:boolean) : string;
  public
    procedure Display(Msg : String);
    function AddUser(body:widestring):string;
    function GetUser(userid:string) : string;
    function UpdateUser(userdetails:string) : string;
    function EnrollUser(userid:string) : string;    

  end;

var
  HttpPostForm: THttpPostForm;
  StartTime: Longword;

procedure SaveLogs(force:boolean);  

implementation

{$R *.DFM}

uses gymsyncmain, cdsutils;

procedure SaveLogs(force:boolean);
var s : widestring;
begin

  if (HttpPostForm.DisplayMemo.Lines.count mod 100 = 0) or force then
  begin
    s := CDS_LoadTextFromFile('atom.logs');
    s := s+ HttpPostForm.DisplayMemo.Lines.text;
    CDS_SaveTextToFile('atom.logs',s);
    HttpPostForm.DisplayMemo.Lines.clear;
  end;


end;


{* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *}
procedure THttpPostForm.Display(Msg : String);

begin
  DisplayMemo.Lines.Add(Msg);
  SaveLogs(false);
end;

function THttpPostForm.EnrollUser(userid:string) : string;
var
  enrollurl : string;
  a : integer;
begin
  enrollurl := enrollapiurl+userid+'/?IPAddress='+ipaddress;
  result := HttpPostForm.HttpPost(enrollurl, '', false);
  if pos('ENROLL REQUEST SENT',uppercase(result)) > 0 then result := 'ok' else result := 'error : '+result;
end;

function THttpPostForm.GetUser(userid:string) : string;
var
  enrollurl : string;
  a : integer;
begin
  enrollurl := personsapiurl+userid;
  result := HttpPostForm.HttpGet(enrollurl,'');
end;


function THttpPostForm.UpdateUser(userdetails:string) : string;
var
  adduserdetails,personnumber, json,updateduser, personid, enrollurl, geturl,res : string;
  jso, jsp : TlkJSONobject;
  ws: TlkJSONstring;
  a : integer;

begin
  jso := TlkJSONobject.Create;
  jsp := TlkJSONobject.Create;

  res := AddUser(userdetails);   // try add
  //HttpPostForm.display('CheckUser Result : '+userdetails+' Result:'+res);

  if res = 'exists' then    // edit the user
  begin
    jso := TlkJSON.ParseText(userdetails) as TlkJSONobject;
    //HttpPostForm.display('GetJSO : '+userdetails);
    personnumber := jso.Field['Person_Number'].Value;
    geturl := personsapiurl+personnumber;
    json := HttpPostForm.HttpGet(geturl,'');    // now get the existing record
    HttpPostForm.display('RequestUpdate : '+personnumber);

    jsp := TlkJSON.ParseText(json) as TlkJSONobject;
    //HttpPostForm.display('GetJSP : '+json);

    personid := jsp.Field['PersonID'].Value;
    HttpPostForm.display('PersonID : '+personid);

    jso.Field['PersonID'].Value :=  personid;
    updateduser := TlkJSON.GenerateText(jso);

    //HttpPostForm.display('User : '+updateduser);

    res := HttpPostForm.HttpPost(personsapiurl, updateduser, true);
    if trim(res) = '' then res := 'ok' else res := trim(res);
    HttpPostForm.display('UPDATED:'+res+' PersonID : '+personid+'   '+updateduser);
    MainMenuForm.SetPersonField('PersonID', PersonID,'p3rdPartyUID','0');
  end else
  begin
    if pos('ERROR',uppercase(res)) > 0 then
      HttpPostForm.display(res)
    else
      HttpPostForm.display('ADDED:'+userdetails);
  end;

  result := res;

  jso.Free;
  jsp.Free;

end;



function THttpPostForm.HttpPost(url:string;body:string; put:boolean) : string;
var
  HTTP: TIdHTTP;
  RequestBody: TStream;
  Responsetext, ResponseBody: string;
begin

  try
    try
      HTTP := TIdHTTP.Create(nil);
      RequestBody := TStringStream.Create(body);
      try
        //HTTP.Request.Accept := 'application/json';
        //HTTP.Request.ContentType := 'application/json';
        if put then
          ResponseBody := HTTP.Put( url, RequestBody)
        else
          ResponseBody := HTTP.Post(url, RequestBody);
        ResponseText := HTTP.ResponseText;
        //Display(ResponseBody);
        //Display(HTTP.ResponseText);
      finally
        RequestBody.Free;
      end;
    except
      on E: EIdHTTPProtocolException do
      begin
        //Display(E.Message);
        ResponseBody := E.ErrorMessage;
      end;
      on E: Exception do
      begin
        //Display(E.Message);
        ResponseBody := E.Message;
      end;
    end;
  finally
    HTTP.Free;
  end;

  result := ResponseBody;

end;

function THttpPostForm.HttpGet(url:string; body:string) : string;
var
  HTTP: TIdHTTP;
  RequestBody: TStream;
  ResponseText,ResponseBody: string;
begin

  try
    try
      HTTP := TIdHTTP.Create(nil);
      RequestBody := TStringStream.Create(body);
      try
        //HTTP.Request.Accept := 'application/json';
        //HTTP.Request.ContentType := 'application/json';
        ResponseBody := HTTP.Get(url);
        ResponseText := HTTP.ResponseText;
        //Display(ResponseBody);
        //Display(HTTP.ResponseText);
      finally
        RequestBody.Free;
      end;
    except
      on E: EIdHTTPProtocolException do
      begin
        //Display(E.Message);
        ResponseBody := E.ErrorMessage;
      end;
      on E: Exception do
      begin
        //Display(E.Message);
        ResponseBody := E.Message;
      end;
    end;
  finally
    HTTP.Free;
  end;

  result := ResponseBody;

end;


{* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *}
procedure THttpPostForm.PostButtonClick(Sender: TObject);
var json : string;
begin
    json := EnrollUser(ActionURLEdit.text);
    Display(json);
end;


procedure THttpPostForm.Button1Click(Sender: TObject);
var json : string;
begin
    json := GetUser(GetUserURL.text);
    Display(json);
end;


procedure THttpPostForm.Button2Click(Sender: TObject);
var json : string;
begin
    json := UpdateUser(PersonBody.lines.text);
    Display(json);    
end;

function THttpPostForm.AddUser(body:widestring):string;
var   res, enrollurl : string;
begin
  enrollurl := personsapiurl;
  res := HttpPostForm.HttpPost(enrollurl, body, false);

  if trim(res) = '' then
    result := 'ok'
  else
  if pos('ALREADY EXISTS',uppercase(res)) > 0 then
    result := 'exists'
  else
    result := 'ERROR:'+res;

end;


procedure THttpPostForm.Button3Click(Sender: TObject);
var
  enrollurl : string;
  a : integer;
  json : string;

begin
  json := AddUser(PersonBody.lines.text);
  Display(json);
end;

procedure THttpPostForm.Button4Click(Sender: TObject);
var
  lHTTP: TIdHTTP;
    lParamList:   TStringStream;
    d : widestring;

begin
  lHTTP := TIdHTTP.Create(nil);
  try
    //d := '{"PersonID":19817,"Person_Name":"NewName","Person_Surname":"NewSurname","Person_Number":"94aa598","DepartmentID":1,"PersonTypeID":1,"PersonStateID":1,"Start_Date":"2014-02-14T00:00:00",'+'"Termination_Date":"1999-09-17T00:00:00","ID_Number":"","Designation":"Director","Access_Group_List":["1"],"ThirdPartyUID":0}';

    lParamList := TStringStream.Create(d);



    d := lHTTP.Post('http://localhost/ATOM/api/UGF1bEJhaWx5OlBCQVBJMTAx/Persons/', lParamList);

    display(d);
  finally
    lHTTP.Free;
    lParamList.Free;
  end;


end;

end.


