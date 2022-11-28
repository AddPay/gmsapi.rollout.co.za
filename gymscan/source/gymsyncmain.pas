unit gymsyncmain;

interface

uses
  Windows, Messages, SysUtils, Variants, Classes, Graphics, Controls, Forms,
  Dialogs, Grids, DBGrids, DB, ADODB, CDSADOQuery, Placemnt, StdCtrls,
  ExtCtrls, kbmMemTable, ComCtrls, Menus, RXShell, RXDBCtrl, UltimDBGrid,
  UltimDBFooter, Buttons, grTool, OleCtrls, SHDocVw, cyBaseWebBrowserD2007,
  cyCustomWebBrowser, cyWebBrowser, SHDocVw_EWB, EwbCore, EmbeddedWB,IdHTTP,
  CDSOnlyOne, IdBaseComponent, IdComponent, IdTCPConnection, IdTCPClient,   ulkjson,
  IdAntiFreezeBase, IdAntiFreeze;

const
  AddPositionURL_c1 = 'http://gmsapi.rollout.co.za/api/gymsync.php';
  AtomURL_c1 = 'http://gmsapi.rollout.co.za/api/atom.php';
  sk_c = '56HJ7UI927DFPT12';

type
  TMainMenuForm = class(TForm)
    DataSource1: TDataSource;
    ADOConnection1: TADOConnection;
    FormStorage1: TFormStorage;
    CDSADOQuery1: TCDSADOQuery;
    UploadTimer: TTimer;
    DownloadTimer: TTimer;
    DownloadTable: TkbmMemTable;
    PersonDS: TDataSource;
    PersonTable: TCDSADOQuery;
    DataSource3: TDataSource;
    PopupMenu1: TPopupMenu;
    ShowStatus1: TMenuItem;
    N1: TMenuItem;
    Exit1: TMenuItem;
    RxTrayIcon1: TRxTrayIcon;
    Timer1: TTimer;
    kbmMemTable1: TkbmMemTable;
    MainMenu1: TMainMenu;
    File1: TMenuItem;
    Close1: TMenuItem;
    Sync1: TMenuItem;
    UpdateNewTransactions1: TMenuItem;
    SyncALLTransactions1: TMenuItem;
    ATOM1: TMenuItem;
    Persons1: TMenuItem;
    Config1: TMenuItem;
    DBgrTool1: TDBgrTool;
    ChangeDetails1: TMenuItem;
    RefreshList1: TMenuItem;
    PageControl1: TPageControl;
    TabSheet1: TTabSheet;
    TabSheet2: TTabSheet;
    UltimDBFooter1: TUltimDBFooter;
    EmbeddedWB1: TEmbeddedWB;
    Panel2: TPanel;
    AtomButton1: TButton;
    StatusBar1: TStatusBar;
    Panel3: TPanel;
    SpeedButton1: TSpeedButton;
    PersonDBGrid: TUltimDBGrid;
    PersonAccessGroupsTable: TCDSADOQuery;
    DownloadTableid: TIntegerField;
    DownloadTablepName: TStringField;
    SyncUsersTable: TCDSADOQuery;
    SpeedButton2: TSpeedButton;
    SpeedButton3: TSpeedButton;
    CDSOnlyOne1: TCDSOnlyOne;
    Button1: TButton;
    Button2: TButton;
    Button14: TButton;
    IdHTTP1: TIdHTTP;
    Button13: TButton;
    AtomTimer: TTimer;
    EnrollUser: TBitBtn;
    IdAntiFreeze1: TIdAntiFreeze;
    CDSADOQuery2: TCDSADOQuery;
    procedure Button1Click(Sender: TObject);
    procedure FormShow(Sender: TObject);
    procedure Button3Click(Sender: TObject);
    procedure UploadTimerTimer(Sender: TObject);
    procedure Button4Click(Sender: TObject);
    procedure SyncAllNOW1Click(Sender: TObject);
    procedure FormCloseQuery(Sender: TObject; var CanClose: Boolean);
    procedure Exit1Click(Sender: TObject);
    procedure ShowStatus1Click(Sender: TObject);
    procedure Timer1Timer(Sender: TObject);
    procedure FormCreate(Sender: TObject);
    procedure Close1Click(Sender: TObject);
    procedure Config1Click(Sender: TObject);
    procedure AtomButton1Click(Sender: TObject);
    procedure BitBtn1Click(Sender: TObject);
    procedure SpeedButton1Click(Sender: TObject);
    procedure PersonDBGridGetCellParams(Sender: TObject; Field: TField;
      AFont: TFont; var Background: TColor; Highlight: Boolean);
    procedure cyWebBrowser1NavigateComplete2(Sender: TObject;
      const pDisp: IDispatch; var URL: OleVariant);
    procedure cyWebBrowser1DocumentComplete(Sender: TObject;
      const pDisp: IDispatch; var URL: OleVariant);
    procedure cyWebBrowser1BeforeNavigate2(Sender: TObject;
      const pDisp: IDispatch; var URL, Flags, TargetFrameName, PostData,
      Headers: OleVariant; var Cancel: WordBool);
    procedure SyncALLTransactions1Click(Sender: TObject);
    procedure DownloadTimerTimer(Sender: TObject);
    procedure BitBtn2Click(Sender: TObject);
    procedure PersonDBGridDrawDataCell(Sender: TObject; const Rect: TRect;
      Field: TField; State: TGridDrawState);
    procedure SpeedButton2Click(Sender: TObject);
    procedure SpeedButton3Click(Sender: TObject);
    procedure Button2Click(Sender: TObject);
    procedure Button14Click(Sender: TObject);
    procedure Button13Click(Sender: TObject);
    procedure AtomTimerTimer(Sender: TObject);
    procedure EnrollUserClick(Sender: TObject);
    procedure FormClose(Sender: TObject; var Action: TCloseAction);
    procedure PersonDBGridDrawColumnCell(Sender: TObject;
      const Rect: TRect; DataCol: Integer; Column: TColumn;
      State: TGridDrawState);

  private
    procedure CheckForUploads(Sender: TObject);
    function UploadTable(a:integer) : integer;
    { Private declarations }
  public
    { Public declarations }
    procedure SetPersonField(idfield, id, valuefield, value: string);
  end;

var
  MainMenuForm: TMainMenuForm;
  IntervalArray : array[1..100] of integer;
  CounterArray : array[1..100] of integer;
  MaxTables : integer;
  ExitClicked : boolean;
  site, ipaddress, secretkey, personsapiurl : string;
  enrollapiurl : string;
  primarydb : boolean;
  AddPositionURL_c, AtomURL_c : string;


implementation

uses cdsdlgs, cdsdb, cdslib, cdsutils, cdsxml, systemdetails, UModifyLookupGenericForm, UModifyLookup, cdshttp,
     uLookupEditor, reportsdatamodule, AddEditPerson, atomwebservice, EnrollError;

{$R *.dfm}



//##################################################################
function RunWebService(fieldlist, fieldvaluelist:Tstringlist):string;
begin

  result := CDS_PostViaHTTP(AddPositionURL_c, fieldlist, fieldvaluelist);

end;



procedure debug(msg:string);
begin
   SystemForm.memo1.lines.add(cds_debug(msg));
   Application.ProcessMessages;
end;

function GetCSVData(secretkey:string):string;
var  fieldlist, fieldvaluelist : tstringlist;

begin
  fieldlist := tstringlist.create;
  fieldvaluelist := tstringlist.create;

  fieldlist.Add('sa');  fieldvaluelist.Add('down');
  fieldlist.Add('sk');  fieldvaluelist.Add(secretkey);

  result := CDS_PostViaHTTP(AddPositionURL_c, fieldlist, fieldvaluelist);
end;

function GetAtomStatus(secretkey:string):string;
var  fieldlist, fieldvaluelist : tstringlist;

begin
  fieldlist := tstringlist.create;
  fieldvaluelist := tstringlist.create;

  fieldlist.Add('action');  fieldvaluelist.Add('getstatus');
  fieldlist.Add('sk');  fieldvaluelist.Add(secretkey);
  fieldlist.Add('actiontype');  fieldvaluelist.Add(site);

  result := CDS_PostViaHTTP(AtomURL_c, fieldlist, fieldvaluelist);
end;



function GetAtomUsers(secretkey:string):string;
var  fieldlist, fieldvaluelist : tstringlist;

begin
  fieldlist := tstringlist.create;
  fieldvaluelist := tstringlist.create;

  fieldlist.Add('action');  fieldvaluelist.Add('geteditusers');
  fieldlist.Add('sk');  fieldvaluelist.Add(secretkey);
  fieldlist.Add('actiontype');  fieldvaluelist.Add(site);  

  result := trim(CDS_PostViaHTTP(AtomURL_c, fieldlist, fieldvaluelist));
end;


function ClearAtomStatus(secretkey:string; action, actiontype:string):string;
var  fieldlist, fieldvaluelist : tstringlist;

begin
  fieldlist := tstringlist.create;
  fieldvaluelist := tstringlist.create;

  fieldlist.Add('action');  fieldvaluelist.Add(action);
  fieldlist.Add('sk');  fieldvaluelist.Add(secretkey);
  fieldlist.Add('actiontype');  fieldvaluelist.Add(actiontype);

  result := CDS_PostViaHTTP(AtomURL_c, fieldlist, fieldvaluelist);
end;



function UpdateID(secretkey:string; id, ids:string):string;
var  fieldlist, fieldvaluelist : tstringlist;

begin
  fieldlist := tstringlist.create;
  fieldvaluelist := tstringlist.create;

  fieldlist.Add('sa');  fieldvaluelist.Add('update');
  fieldlist.Add('ids');  fieldvaluelist.Add(ids);
  fieldlist.Add('sk');  fieldvaluelist.Add(secretkey);

  result := CDS_PostViaHTTP(AddPositionURL_c, fieldlist, fieldvaluelist);
end;


function SyncJSON(secretkey, data:string; wherecolumn:string):string;
var  fieldlist, fieldvaluelist : tstringlist;

begin
  fieldlist := tstringlist.create;
  fieldvaluelist := tstringlist.create;

  fieldlist.Add('da');  fieldvaluelist.Add(data);
  fieldlist.Add('wc');  fieldvaluelist.Add(wherecolumn);
  fieldlist.Add('sa');  fieldvaluelist.Add('up');
  fieldlist.Add('sk');  fieldvaluelist.Add(secretkey);

  result := CDS_PostViaHTTP(AddPositionURL_c, fieldlist, fieldvaluelist);

end;



procedure TMainMenuForm.Button1Click(Sender: TObject);

var
enabled, totaltables, a : integer;
starttime : integer;
tableenabled : boolean;
  result,ip, enrollapiurl, syncfield, syncvalue, httpresult, json, comma, sql, wherecolumns, inifile, tablenames, tablename : string;
maxrecordspersession : integer;
var  fieldlist, fieldvaluelist : tstringlist;
var  lParamList: TStringList;
var
  pStream: TMemoryStream;
  IdHTTP1           : TIdHTTP;
  buffer : array[1..100] of char;


begin
  systemform.memo1.Lines.clear;

  inifile := ExtractFilepath(paramstr(0))+'\gymsynctables.ini';
  enrollapiurl := cds_LoadValueFromINI(inifile,'TABLES', 'enrollapiurl', '');

  fieldlist := tstringlist.create;
  fieldvaluelist := tstringlist.create;

  ip := '192.168.0.6';
  for a := 1 to length(ip) do buffer[a] := ip[a];
  fieldlist.Add('IPAddress');  fieldvaluelist.Add(ip);
  enrollapiurl := enrollapiurl + '999/';


  lParamList := TStringList.Create;
  lParamList.Add('IPAddress='+ip);

  result := CDS_PutViaHTTP(enrollapiurl,lParamList);

  systemform.memo1.Lines.add(result);

  StatusBar1.Panels[1].Text := '';
  StatusBar1.refresh;

  systemform.show;

end;

procedure TMainMenuForm.FormShow(Sender: TObject);
var
  syncinterval,a : integer;
  dbconnection, syncenabled, inifile, tablenames, tablename : string;

begin
  shortdateformat := 'mm/dd/yyyy';


  debug('Startup....');
  inifile := ExtractFilepath(paramstr(0))+'\gymsynctables.ini';
  downloadtimer.Interval := 1000*cds_LoadValueFromINI(inifile,'TABLES', 'downloadcheckinterval', 120);

  enrollapiurl := cds_LoadValueFromINI(inifile,'TABLES', 'enrollapiurl', '');
  personsapiurl := cds_LoadValueFromINI(inifile,'TABLES', 'personsapiurl', '');

  addpositionurl_c := cds_LoadValueFromINI(inifile,'TABLES', 'addpositionurl', 'https://matiesgym.rollout.co.za/api/gymsync.php');
  atomurl_c := cds_LoadValueFromINI(inifile,'TABLES', 'atomurl', 'https://matiesgym.rollout.co.za/api/atom.php');

  secretkey := cds_LoadValueFromINI(inifile,'TABLES', 'secretkey', '');
  ipaddress := cds_LoadValueFromINI(inifile,'TABLES', 'ipaddress', CDS_LocalIP());
  syncinterval := cds_LoadValueFromINI(inifile,'TABLES', 'webserviceinterval', 5);
  syncenabled := cds_LoadValueFromINI(inifile,'TABLES', 'webserviceenabled', '0');
  site := cds_LoadValueFromINI(inifile,'TABLES', 'site', '1');
  dbconnection := cds_LoadValueFromINI(inifile,'TABLES', 'dbconnection', SystemForm.edit1.Text);
  primarydb := cds_LoadValueFromINI(inifile,'TABLES', 'primarydb', false);

  MaxTables := cds_LoadValueFromINI(inifile,'TABLES', 'total', 0);
  for a := 1 to MaxTables do
  begin
    IntervalArray[a] := cds_LoadValueFromINI(inifile,'TABLE'+inttostr(a), 'uploadcheckinterval', 10);;
    CounterArray[a] := 0;
  end;


  ADOConnection1.connected := false;
  ADOConnection1.ConnectionString := dbconnection;
  try
    if dbconnection <> '' then
    begin
      ADOConnection1.connected := true;
      PersonTable.Open;
      PersonDBGrid.AutoWidth := awUniform;
      PersonAccessGroupsTable.Open;
    end;
  except
    debug('on db connection');
  end;
  debug(SystemForm.edit1.Text);


  debug('Open Persons...');

  //PersonDBGrid.AutoWidthColumns;

  CDS_MaximiseHeightAndWidth(MainMenuForm);

  if (uppercase(paramstr(1)) = 'AUTOSTART') or SystemForm.cbautostart.checked then
  begin
    Button3Click(nil);
    //DownloadTimer.Enabled := true;
  end;

//  if (timer1.Enabled = true) and SystemForm.cbAutohide.checked then timer1.Enabled := true else timer1.Enabled := false;

  debug('Running...');

  AtomTimer.Interval := (syncinterval*1000);
//  AtomTimer.Enabled := syncenabled = '1';


end;

procedure TMainMenuForm.Button3Click(Sender: TObject);
begin
//  if sender = nil then UploadTimer.enabled := true else
  begin
//    UploadTimer.enabled := not UploadTimer.enabled;
  end;
//  if UploadTimer.enabled then TButton(sender).Caption := 'Loop Sync : RUNNING' else TButton(sender).Caption := 'Loop Sync : STOPPED';
end;

procedure TMainMenuForm.UploadTimerTimer(Sender: TObject);
begin
  UploadTimer.Enabled := false;
  SystemForm.ProgressBar1.Position := SystemForm.ProgressBar1.Position + 1;
  if SystemForm.ProgressBar1.Position >= 99 then SystemForm.ProgressBar1.Position := 1;
  CheckForUploads(sender);
  UploadTimer.Enabled := false;
end;

function FoundID(id,idfield:string): boolean;
var foundid : boolean;
begin
  with MainMenuForm.CDSADOQuery1  do
  begin
    close;
    sql.text := 'select PersonID from Persons where rtrim(ltrim('+idfield+')) = '+#39+trim(id)+#39;
    open;
//    SystemForm.memo1.Lines.Add(sql.text);
  end;

  result := not MainMenuForm.CDSADOQuery1.IsEmpty;

end;

procedure UpdateGroupId(id,GroupAccessID,surname: string);
begin

  with Mainmenuform do
  try
    CDS_FilterDoubleCriteria(PersonAccessGroupsTable,'PersonID','=', id, 'and',  'AccessGroupID', '=', GroupAccessID);
    if PersonAccessGroupsTable.IsEmpty then
    with PersonAccessGroupsTable do
    begin
      append;
      fieldbyname('PersonID').asstring := id;
      fieldbyname('AccessGroupID').asstring := GroupAccessID;
      post;
    end;
  except
       on e:exception do
       begin
         //ShowMessage(e.message+' : '+id+' - '+GroupAccessID+' '+surname);
         PersonAccessGroupsTable.cancel;
       end;
  end;

end;

function DownloadUserContracts(showerror:boolean) : integer;

var
pPersonNo, idfield, id, updateids, pIDNo, MappedFields, xml, httpresult, json, comma, sql, wherecolumns, inifile, tablenames, tablename : string;
foundtheid : boolean;
linestr, sFileName,  indexidfield, tableenabled, syncfield, syncvalue : string;
a, personid, maxrecordspersession : integer;


begin
  inifile := ExtractFilepath(paramstr(0))+'gymsynctables.ini';
  idfield := cds_LoadValueFromINI(inifile,'TABLES', 'idfield', '0');

  indexidfield := cds_LoadValueFromINI(inifile,'TABLES', 'indexid', '0');
  tableenabled := cds_LoadValueFromINI(inifile,'TABLES', 'enabled', '0');

  if (tableenabled = '1') then
  with Mainmenuform do
  begin
    xml := trim(GetCSVData(secretkey));

    if xml = '' then
    begin
      SystemForm.memo1.Lines.Add('No User Data');
      result := 0;
    end
    else
    if (pos('Host not found',xml) > 0) or (pos('"id"',xml)=0) then
    begin
      result := 2;
      SystemForm.memo1.Lines.Add('No Internet - '+xml);
    end
    else
    if length(xml) > 10 then
    begin

      //SystemForm.memo1.Lines.Add(xml);
      sFileName := ExtractFilepath(paramstr(0))+'suspi_users.csv';

      DownloadTable.Active := false;
      cds_SavetextToFile(sfilename,trim(xml));

      DownloadTable.PersistentFile := sfilename;
      DownloadTable.Open;

      updateids := '';

      SyncUsersTable.Close;
      SyncUsersTable.Open;
      SyncUsersTable.DisableControls;
      a := 0;
      with DownloadTable do
      if active then
      begin
        First;
        if not eof then
        repeat
          pIDNo := fieldbyname(idfield).asstring;
          id := fieldbyname(indexidfield).asstring;

          if StrToInt(id) > 0 then
          begin
            try
              //foundtheid := FoundID(pIDNo, idfield);
              foundtheid := cds_ADOLocate(SyncUsersTable,idfield,pIDNo,false);
              linestr := pIDNo+' - ';
              if foundtheid then
              begin
                 pPersonNo := SyncUsersTable.fieldbyname('pPersonNumber').asstring;
                 CDS_UpdateRecordFromOneTableToAnother(DownloadTable, SyncUsersTable,false);
                 CDS_EditField(SyncUsersTable, 'pPersonNumber', pPersonNo);
                 linestr := linestr + 'found '+DownloadTable.fieldbyname('id').asstring+' '+pPersonNo;
                 SyncUsersTable.Post;
              end
               else
              begin
                 CDS_CopyRecordFromOneTableToAnother(DownloadTable, SyncUsersTable,false);
                 CDS_EditField(SyncUsersTable, 'DepartmentID', 1);
                 CDS_EditField(SyncUsersTable, 'PersonTypeID', 1 );
                 CDS_EditField(SyncUsersTable, 'PersonStateID',1);
                 CDS_EditField(SyncUsersTable, 'pPersonNumber', 'GMS'+DownloadTable.fieldbyname('id').asstring+'000'+inttostr(random(1000)));
                 CDS_EditField(SyncUsersTable, 'pDesignation', 'Member');
                 CDS_EditField(SyncUsersTable, 'pPresence', 0);
                 CDS_EditField(SyncUsersTable, 'pPresenceSiteID',0);
                 CDS_EditField(SyncUsersTable, 'pPresenceUpdated','01/01/2000');
                 CDS_EditField(SyncUsersTable, 'PayGroupID',0);
                 CDS_EditField(SyncUsersTable, 'ShiftCycleID',0);
                 CDS_EditField(SyncUsersTable, 'ShiftCycleDay',0);
                 CDS_EditField(SyncUsersTable, 'CycledShiftUpdate', DownloadTable.fieldbyname('pStartDate').asstring);
                 CDS_EditField(SyncUsersTable, 'pTAClocker',0);
                 CDS_EditField(SyncUsersTable, 'pFONLOFF',0);
                 CDS_EditField(SyncUsersTable, 'p3rdPartyUID',0);
                 CDS_EditField(SyncUsersTable, 'pTerminalDBNumber',0);
                 linestr := linestr + 'notfound '+DownloadTable.fieldbyname('id').asstring;
                 SyncUsersTable.Post;
              end;
            except
               on e:exception do
               begin
                 if ShowError then ShowMessage(e.message);
                 SystemForm.memo1.Lines.Add(e.message);
                 SyncUsersTable.cancel;
               end;
            end;
            UpdateGroupId(SyncUsersTable.fieldbyname('personID').asstring ,DownloadTable.fieldbyname('AccessGroupID').asstring,DownloadTable.fieldbyname('pIDNo').asstring);
            updateids := updateids + id +',';
          end;

          {
          personid := PersonTable.fieldbyname(personidfield).AsInteger;

          if cds_AdoLocate(tcustomadodataset(PersonAccessGroupsTable),personidfield, personid, false) then
          begin
            sql = 'update accesscontrolgroups set
          end
            else
          begin
          end; }


          //SystemForm.memo1.Lines.Add(linestr);
          Next;
          application.processmessages;
          inc(a);

          if a mod 10 = 0 then
          begin
            MainMenuform.statusbar1.panels[0].text := inttostr(a);
            MainMenuform.statusbar1.refresh;
          end;

        until eof;

        if pos(',',updateids) > 0 then
          updateids := copy(updateids,1,length(updateids)-1);

        updateids := UpdateID(secretkey, idfield, updateids);

        SystemForm.memo1.Lines.Add('User Data :'+updateids);
        cds_RefreshDataset(PersonTable);
        SyncUsersTable.EnableControls;
        result := 1;
      end;
    end else
    begin
      result := 3;
      SystemForm.memo1.Lines.Add('Unknown error connecting to Internet '+xml);
    end;

  end;

end;

procedure TMainMenuForm.Button4Click(Sender: TObject);
begin
  DownloadUserContracts(false);
end;


procedure TMainMenuForm.SyncAllNOW1Click(Sender: TObject);
begin
  Button1Click(Sender);
  Button4Click(Sender);
end;

function TMainMenuForm.UploadTable(a:integer) : integer;
var
enabled, totaltables : integer;
starttime : integer;
tableenabled : boolean;
 httpresult, json, comma, sql, wherecolumns, inifile, tablenames, tablename : string;
syncfield, syncvalue : string;
maxrecordspersession : integer;


begin
     inifile := ExtractFilepath(paramstr(0))+'\gymsynctables.ini';

     starttime := gettickcount;
     tablename := cds_LoadValueFromINI(inifile,'TABLE'+IntTostr(a), 'tablename', '' );;
     sql := cds_LoadValueFromINI(inifile,'TABLE'+IntTostr(a), 'sql', '' );;
     wherecolumns := cds_LoadValueFromINI(inifile,'TABLE'+IntTostr(a), 'where', '' );;
     tableenabled := cds_LoadValueFromINI(inifile,'TABLE'+IntTostr(a), 'enabled', 0) = 1;

     syncfield := cds_LoadValueFromINI(inifile,'TABLE'+IntTostr(a), 'syncfield', '');
     syncvalue := cds_LoadValueFromINI(inifile,'TABLE'+IntTostr(a), 'syncvalue', '');

     maxrecordspersession := cds_LoadValueFromINI(inifile,'TABLE'+IntTostr(a), 'maxrecordspersession', 500);

     if tableenabled then
     begin
       StatusBar1.Panels[1].Text := 'Upload '+tablename;
       StatusBar1.refresh;

       CDSADOQuery1.Close;
       CDSADOQuery1.SQL.text := sql;

       if (maxrecordspersession>0) then
       begin
         CDSADOQuery1.SQL.text := CDS_SearchAndReplace(CDSADOQuery1.SQL.text, 'SELECT', 'SELECT TOP '+inttostr(maxrecordspersession)+' ',false);
       end;

       debug(tablename+' : START');

       CDSADOQuery1.Open;
       json := '{'+CDS_DatasetToJSON(tablename, CDSADOQuery1, '', false, true)+'}';

       CDS_SaveTextToFile(tablename+'.json',json);
       debug(tablename+' : Request t='+inttostr(starttime-gettickcount));

       httpresult := SyncJSON(secretkey, json, wherecolumns);

       if (syncfield<>'') and (syncvalue<>'') then
       begin
          CDS_EditAndPostFieldInAllRecords(CDSADOQuery1, syncfield, syncvalue);
       end;

       debug(tablename+' FIN POST : '+httpresult+ ' t='+inttostr(starttime-gettickcount));
     end;

     result := 0;

end;

procedure TMainMenuForm.CheckForUploads(Sender: TObject);
var
  a : integer;

begin
  RxTrayIcon1.Animated := true;
  StatusBar1.Panels[1].Text := 'Checking Data...';
  for a := 1 to MaxTables do
  begin
    inc(CounterArray[a], 1);
    if CounterArray[a] >= IntervalArray[a] then CounterArray[a] := UploadTable(a);
  end;
  RxTrayIcon1.Animated := false;
  StatusBar1.Panels[1].Text := '';
  StatusBar1.refresh;
end;

procedure TMainMenuForm.FormCloseQuery(Sender: TObject; var CanClose: Boolean);
begin
  Canclose := ExitClicked{ or not ConfigForm.AutostartDownloads.checked};
  if not CanClose then Hide;
end;

procedure TMainMenuForm.Exit1Click(Sender: TObject);
begin
  ExitClicked := true;
  Close;
end;

procedure TMainMenuForm.ShowStatus1Click(Sender: TObject);
begin
  MainmenuForm.Show;
end;

procedure TMainMenuForm.Timer1Timer(Sender: TObject);
begin
  timer1.Enabled := false;
//  MainMenuForm.Close;
end;

procedure TMainMenuForm.FormCreate(Sender: TObject);
begin
  formstorage1.RestoreFormPlacement;
  Tabsheet2.Destroy;
end;

procedure TMainMenuForm.Close1Click(Sender: TObject);
begin
  Close;
end;

procedure TMainMenuForm.Config1Click(Sender: TObject);
begin
  if InputBox('Password','Password','') = 'gymsync' then SystemForm.Show;
end;

procedure TMainMenuForm.AtomButton1Click(Sender: TObject);
begin
  EmbeddedWB1.Navigate(SystemForm.AtomEdit1.Text);
end;

procedure TMainMenuForm.SetPersonField(idfield, id, valuefield,value:string);
var  sSql : string;
begin
  sSql := 'update Persons set ['+valuefield+'] = '+CDS_EncloseSQL(value)+' where '+idfield+ ' = '+CDS_EncloseSQL(id);
  try
    CDS_ExecuteADOQuery(MainMenuForm.CDSADOQuery2, sSQL);
    HttpPostForm.display('SetPerson:'+valuefield+'->'+value+' : '+idfield+'='+id);
  except
  end;  
end;



procedure TMainMenuForm.BitBtn1Click(Sender: TObject);
var SavedOk : boolean;
    id : string;
begin
  if not assigned(AddEditPersonForm) then AddEditPersonForm := TAddEditPersonForm.Create(Self);
  with AddEditPersonForm do
  repeat
    try
      PersonTable.Edit;
      IF ShowModal=mrOK then  {Only save if not Cancel}
      begin
        PersonTable.Post;
        id := PersonTable.fieldbyname('PersonID').AsString;
        ClearAtomStatus(secretkey,'resenduser',id);
        SetPersonField('PersonID', id,'p3rdPartyUID','0');
        CDS_RefreshDataset(PersonTable);
        PersonDBGrid.AutoWidth := awUniform;        
      end
      else PersonTable.Cancel;
      SavedOk := true;
    except
      ShowMessage('Person Number exists...');
      SavedOk := false;
    end;
  until SavedOk;
end;

procedure TMainMenuForm.SpeedButton1Click(Sender: TObject);
var result : integer;
begin

   cds_ShowSingleMessage('Updating latest data....please wait');
   result := DownloadUserContracts(true);
   CDS_RefreshDataset(PersonTable);
   PersonDBGrid.AutoWidth := awUniform;
   cds_CloseMessage();

   case result of
     2: cds_ErrorMessage('No Internet. Please check your connections', true);
     3: cds_ErrorMessage('Unknown error connecting to Internet. Please check your connections', true);
   end; {case}

end;

procedure TMainMenuForm.PersonDBGridGetCellParams(Sender: TObject;
  Field: TField; AFont: TFont; var Background: TColor; Highlight: Boolean);
begin
  if (Field.DisplayName = 'pTerminationDate') then
  begin
    If now() >  Field.AsDateTime then
    begin
      Background := clred;
    end;
  end else
  if (Field.DisplayName = 'Finger1') or (Field.DisplayName = 'Finger2') then
  begin
      If (Field.AsString = 'Poor') or (Field.AsString = '') then Background := clTeal;
  end else
  if (Field.DisplayName = 'PersonState') then
  begin
  end

end;

procedure TMainMenuForm.cyWebBrowser1NavigateComplete2(Sender: TObject;
  const pDisp: IDispatch; var URL: OleVariant);
begin
  StatusBar1.Panels[0].Text := '';
end;

procedure TMainMenuForm.cyWebBrowser1DocumentComplete(Sender: TObject;
  const pDisp: IDispatch; var URL: OleVariant);
begin
  StatusBar1.Panels[0].Text := '';
end;

procedure TMainMenuForm.cyWebBrowser1BeforeNavigate2(Sender: TObject;
  const pDisp: IDispatch; var URL, Flags, TargetFrameName, PostData,
  Headers: OleVariant; var Cancel: WordBool);
begin
  StatusBar1.Panels[0].Text := 'Loading...';
end;

procedure TMainMenuForm.SyncALLTransactions1Click(Sender: TObject);
begin
 MainMenuForm.Button4Click(Sender);
end;

procedure TMainMenuForm.DownloadTimerTimer(Sender: TObject);
begin
  StatusBar1.Panels[1].Text := 'Checking users...';
  StatusBar1.refresh;
//  DownloadTimer.enabled := false;
  MainMenuForm.Button4Click(Sender);
//  DownloadTimer.enabled := true;
  StatusBar1.Panels[1].Text := '';
  StatusBar1.refresh;    
end;

procedure TMainMenuForm.BitBtn2Click(Sender: TObject);
var id : string;
FingerP : boolean;
begin
  if cds_ConfirmMessage('Are you sure you want to delete user '+PersonTable.fieldbyname('pSurname').asstring, true) = mrYes then
  with PersonTable do
  begin
    FingerP := (fieldbyname('Finger1').asstring <> '') and (fieldbyname('Finger2').asstring <> '') and (cds_ConfirmMessage('Warning : This user has fingerprints allocated. Are you sure you want to delete. All fingerprints will be lost?', true) = mrYes);
    if (fieldbyname('Finger1').asstring = '') or (fieldbyname('Finger2').asstring = '') or FingerP then
    begin
      id := fieldbyname('PersonID').asstring;
      if CDS_ExecuteADOQuery(CDSADOQuery1,'delete PersonAccessGroups where PersonID = '+id) then
      begin
        delete;
        CDS_InformationMessage('User deleted', false);
      end
      else
      begin
        cds_ErrorMessage('Error deleting user. Please speak to system administrator', true);
      end;
    end;
  end;
end;

procedure TMainMenuForm.PersonDBGridDrawDataCell(Sender: TObject;
  const Rect: TRect; Field: TField; State: TGridDrawState);

  var st : string;
begin
    if (Field.DisplayName = 'PersonState') then
    begin
      if Field.AsString = '1' then  st := 'Active' else
      if Field.AsString = '2' then st := 'Expired' else
      if Field.AsString = '3' then st := 'Deleted';

      PersonDBGrid.Canvas.TextRect(Rect, rect.left+3,rect.top+2,st);
    end;

end;

procedure TMainMenuForm.SpeedButton2Click(Sender: TObject);
begin
  DBgrTool1.ExecSearch;
end;

procedure TMainMenuForm.SpeedButton3Click(Sender: TObject);
begin
  DBgrTool1.ExecFilter;
end;

procedure TMainMenuForm.Button2Click(Sender: TObject);

var
enabled, totaltables, a : integer;
starttime : integer;
tableenabled : boolean;
  result,ip, enrollapiurl, syncfield, syncvalue, httpresult, json, comma, sql, wherecolumns, inifile, tablenames, tablename : string;
maxrecordspersession : integer;
var  fieldlist, fieldvaluelist : tstringlist;
var
  pStream: TMemoryStream;
  IdHTTP1           : TIdHTTP;
  buffer : array[1..100] of char;


begin
  systemform.memo1.Lines.clear;

  inifile := ExtractFilepath(paramstr(0))+'\gymsynctables.ini';
  enrollapiurl := cds_LoadValueFromINI(inifile,'TABLES', 'personsapiurl', '');

  fieldlist := tstringlist.create;
  fieldvaluelist := tstringlist.create;

  enrollapiurl := enrollapiurl + '/'+'12345';

 // result := CDS_PutViaHTTP(enrollapiurl, ip);

  IdHTTP1 := TIdHTTP.create(nil);
  try
    enrollapiurl := 'http://www.google.com';
    result :=     IdHTTP1.Get(enrollapiurl);
    systemform.memo1.lines.add(result);
  finally
    pStream.Free;
  end;


  systemform.memo1.Lines.add(result);

  StatusBar1.Panels[1].Text := '';
  StatusBar1.refresh;

  systemform.show;

end;

procedure TMainMenuForm.Button14Click(Sender: TObject);

var
  lHTTP: TIdHTTP;
    lParamList:   TStringStream;
    d : widestring;

begin
  lHTTP := TIdHTTP.Create(nil);
  try
    lParamList := TStringStream.Create('');
    d := '{"PersonID":19817,"Person_Name":"NewName","Person_Surname":"NewSurname","Person_Number":"94545455498","DepartmentID":1,"PersonTypeID":1,"PersonStateID":1,"Start_Date":"2014-02-14T00:00:00",'+'"Termination_Date":"1999-09-17T00:00:00","ID_Number":"","Designation":"Director","Access_Group_List":["1"],"ThirdPartyUID":0}';
    lParamList.WriteString(d);

    d := lHTTP.Post('http://localhost:8080/ATOM/api/UGF1bEJhaWx5OlBCQVBJMTAx/Persons/', lParamList);

    systemform.memo1.Lines.add(d);
    systemform.show;
  finally
    lHTTP.Free;
    lParamList.Free;
  end;

end;

procedure TMainMenuForm.Button13Click(Sender: TObject);
begin
  HttpPostForm.show;
end;


procedure EditUsers();
var res,id, json : string;
 jso, jsp : TlkJSONobject;

 begin
  json := GetAtomUsers(secretkey);
  //HttpPostForm.display('GetUser:'+json);
  json := CDS_SearchAndReplace(json,'[{', '{',false);
  json := CDS_SearchAndReplace(json,'}]', '}',false);
  json := CDS_SearchAndReplace(json,'"[\"1\"]"', '["1"]',false);
  json := CDS_SearchAndReplace(json,'"[\"2\"]"', '["2"]',false);
  json := CDS_SearchAndReplace(json,'"[\"0\"]"', '["0"]',false);
  json := CDS_SearchAndReplace(json,'null'     , ''     ,false);

//  showmessage(json);

  if length(json) > 20 then
  begin
    res := HttpPostForm.UpdateUser(json);
    if res = 'ok' then
    begin
      jso := TlkJSONobject.Create;
      jso := TlkJSON.ParseText(json) as TlkJSONobject;
      id := jso.Field['id'].Value;
      ClearAtomStatus(secretkey,'updateusers',id);
      HttpPostForm.display('Updated:'+id);
      jso.Free;
    end else
    begin
      HttpPostForm.display('Error:'+res+' '+json);
    end;
  end;

end;

procedure AddUsers();
var json : string;
begin



end;

procedure EnrollTheUser(personnumber:string);
var json : string;
begin
    json := HttpPostForm.EnrollUser(personnumber);
    ClearAtomStatus(secretkey,'clearstatus','get_enroll');
    HttpPostForm.Display(json);
    if json <> 'ok' then EnrollErrorForm.Showmodal;

end;


procedure ActionAtom(status:string);
var statuslist : tstringlist;
begin
  statuslist := tstringlist.Create();
  CDS_ParseItemsToList(trim(status),',',statuslist);

  if (statuslist.count = 3)  then
  begin
      //HttpPostForm.Display(status);  
      if (statuslist[0] = '1') and primarydb then   //edit user
      begin
        HttpPostForm.Display(status);
        EditUsers();
      end;

      if (statuslist[1] <> '0') and (statuslist[2] = site) then  // enroll user
      begin
        HttpPostForm.Display(status);
        EnrollTheUser(statuslist[1]);
      end;  
  end;
  //showmessage(status);

end;

procedure TMainMenuForm.AtomTimerTimer(Sender: TObject);
var
  status : string;

begin
  AtomTimer.enabled := false;
  if CDS_InternetConnected then
  begin
    try
      statusbar1.Panels[0].Text := 'Check Atom....';
      status := GetAtomStatus(secretkey);
      ActionAtom(status);
      statusbar1.Panels[0].Text := ' ';
    except
      on e:exception do
      begin
         statusbar1.Panels[0].Text := e.message;
         HttpPostForm.Display(e.message);
      end;   
    end;
  end else
  begin
    statusbar1.Panels[0].Text := 'No Internet..';
  end;

//  AtomTimer.enabled := true;;
  statusbar1.refresh;
end;

procedure TMainMenuForm.EnrollUserClick(Sender: TObject);
var personnumber : string;
begin
  personnumber := PersonDBGrid.DataSource.DataSet.fieldbyname('pPersonNumber').AsString;
  EnrollTheUser(personnumber);
  SetPersonField('pPersonNumber', personnumber ,'p3rdPartyUID','0');
end;

procedure TMainMenuForm.FormClose(Sender: TObject;
  var Action: TCloseAction);
begin
  SaveLogs(true);
end;

procedure TMainMenuForm.PersonDBGridDrawColumnCell(Sender: TObject;
  const Rect: TRect; DataCol: Integer; Column: TColumn;
  State: TGridDrawState);
var
  grid : TDBGrid;
  st, maskValue : String;
  aRect : TRect;
begin
  aRect := Rect;
  grid := sender as TDBGrid;

  if (Column.Field.FieldName = 'PersonState') then
  begin
    if Column.Field.Text = '1' then st := 'Active' else
    if Column.Field.Text = '2' then st := 'Expired' else
    if Column.Field.Text = '3' then st := 'Deleted';
    grid.Canvas.FillRect(Rect);
    DrawText(grid.Canvas.Handle, PChar(st), Length(st), aRect, DT_SINGLELINE or DT_LEFT or DT_VCENTER);
  end;

end;


end.
